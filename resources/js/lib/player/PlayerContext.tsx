import React from 'react';
import type { TrackSummary } from '../types';

/**
 * The global playback engine. Replaces the SoundManager2 player service from
 * the Angular app with a plain HTML5 Audio element. Semantics carried over:
 *  - playTracks() replaces the whole queue
 *  - playing the current track again toggles pause
 *  - repeat cycles off → repeat-queue → repeat-one
 *  - volume persists in the pfm-volume cookie
 * Play counting needs no client work — the stream request logs it.
 */

export type RepeatMode = 'off' | 'queue' | 'one';

/** A queue entry carries stable identity of its own — the same track can sit
 *  in the queue twice, and sortable drag-and-drop needs to tell them apart. */
export type QueueEntry = TrackSummary & { queueId: string };

function stamp(track: TrackSummary): QueueEntry {
    const existing = (track as QueueEntry).queueId;
    return {
        ...track,
        queueId: existing ?? (typeof crypto !== 'undefined' && 'randomUUID' in crypto
            ? crypto.randomUUID()
            : 'q' + Date.now().toString(36) + Math.random().toString(36).slice(2)),
    };
}

export interface PlayerState {
    queue: QueueEntry[];
    index: number;
    current: QueueEntry | null;
    isPlaying: boolean;
    /** 0–100 */
    volume: number;
    repeatMode: RepeatMode;
    playTracks: (tracks: TrackSummary[], index?: number) => void;
    /** Insert a track directly after the one currently playing. */
    queueNext: (track: TrackSummary) => void;
    /** Append a track to the end of the queue. */
    queueLast: (track: TrackSummary) => void;
    /** Jump to a queue position (or toggle pause if it's the current one). */
    playAt: (index: number) => void;
    removeFromQueue: (index: number) => void;
    /** Reorder the queue, keeping the playing track playing. */
    moveInQueue: (from: number, to: number) => void;
    playPause: () => void;
    playNext: () => void;
    playPrev: () => void;
    seekTo: (fraction: number) => void;
    setVolume: (volume: number) => void;
    toggleRepeat: () => void;
    /** Is this track the one loaded in the player (playing or paused)? */
    isCurrent: (id: number | string | undefined) => boolean;
}

const PlayerContext = React.createContext<PlayerState | null>(null);

/** Playback position values, updated ~4×/second while audio plays. Kept out
 *  of React state (and out of PlayerState) so the churn only re-renders the
 *  components that opt in via usePlayerTime() — i.e. the player bar — rather
 *  than every usePlayer() consumer in the tree. */
export interface PlayerTime {
    /** 0–1 through the current track */
    progress: number;
    /** 0–1 buffered */
    buffered: number;
    /** seconds */
    elapsed: number;
    duration: number;
}

interface PlayerTimeStore {
    subscribe: (onChange: () => void) => () => void;
    getSnapshot: () => PlayerTime;
}

const PlayerTimeContext = React.createContext<PlayerTimeStore | null>(null);

/** sessionStorage: the queue is a per-tab listening session — localStorage
 *  would make tabs fight over it and resurrect stale queues.
 *  v2: persisted entries snapshot the full streams map, so the Opus rollout
 *  bumps the key to discard queues that predate the format. */
const QUEUE_STORAGE_KEY = 'pfm-queue-v2';

interface PersistedQueue {
    queue: QueueEntry[];
    index: number;
    repeatMode: RepeatMode;
    elapsed: number;
    /** Ground truth from the audio element — survives bad track metadata. */
    duration?: number;
}

function readPersistedQueue(): PersistedQueue | null {
    try {
        const raw = sessionStorage.getItem(QUEUE_STORAGE_KEY);
        if (!raw) return null;
        const saved = JSON.parse(raw) as PersistedQueue;
        if (!Array.isArray(saved.queue) || saved.queue.length === 0) return null;
        return saved;
    } catch {
        return null;
    }
}

function readVolumeCookie(): number {
    if (typeof document === 'undefined') return 100;
    const m = document.cookie.match(/(?:^|;\s*)pfm-volume=([^;]+)/);
    const v = m ? parseInt(m[1], 10) : NaN;
    return isNaN(v) ? 100 : Math.min(100, Math.max(0, v));
}

let liveAudio: HTMLAudioElement | null = null;

/** Whether audio is actively playing right now — usable outside React
 *  (e.g. deciding if login must avoid a full page load). */
export function isAudioPlaying(): boolean {
    return !!liveAudio && !liveAudio.paused && !liveAudio.ended;
}

/** Streaming prefers Opus, falling back to AAC then MP3 for clients that
 *  can't play it (pre-18.4 Safari / older Apple devices). The server only
 *  advertises streams whose file exists, so a track the Opus backfill
 *  hasn't reached yet (streams.opus === null) degrades to MP3 here rather
 *  than erroring. */
function pickSource(track: TrackSummary, audio: HTMLAudioElement): string | null {
    const candidates: [string | null | undefined, string][] = [
        [track.streams.opus, 'audio/ogg; codecs="opus"'],
        [track.streams.aac, 'audio/mp4; codecs="mp4a.40.2"'],
        [track.streams.mp3, 'audio/mpeg'],
        [track.streams.ogg, 'audio/ogg; codecs="vorbis"'],
    ];
    for (const [url, mime] of candidates) {
        if (url && audio.canPlayType(mime)) return url;
    }
    return track.streams.mp3 ?? track.streams.opus ?? track.streams.ogg ?? track.streams.aac ?? null;
}

export function PlayerProvider({ children }: { children: React.ReactNode }) {
    const audioRef = React.useRef<HTMLAudioElement | null>(null);
    const [queue, setQueue] = React.useState<QueueEntry[]>([]);
    const [index, setIndex] = React.useState(0);
    const [isPlaying, setIsPlaying] = React.useState(false);
    const [volume, setVolumeState] = React.useState(readVolumeCookie);
    const [repeatMode, setRepeatMode] = React.useState<RepeatMode>('off');

    // Position/buffer values live in a subscription store, not React state —
    // timeupdate fires ~4×/second and must not re-render the provider.
    const timeRef = React.useRef<PlayerTime>({ progress: 0, buffered: 0, elapsed: 0, duration: 0 });
    const timeListeners = React.useRef(new Set<() => void>());
    const setTime = React.useCallback((patch: Partial<PlayerTime>) => {
        timeRef.current = { ...timeRef.current, ...patch };
        timeListeners.current.forEach((notify) => notify());
    }, []);
    const timeStore = React.useMemo<PlayerTimeStore>(() => ({
        subscribe: (onChange) => {
            timeListeners.current.add(onChange);
            return () => timeListeners.current.delete(onChange);
        },
        getSnapshot: () => timeRef.current,
    }), []);

    // Refs mirror state the audio event handlers need without re-binding.
    const stateRef = React.useRef({ queue, index, repeatMode });
    stateRef.current = { queue, index, repeatMode };
    const elapsedRef = React.useRef(0);
    const durationRef = React.useRef(0);
    const pendingSeekRef = React.useRef(0);
    const lastPersistRef = React.useRef(0);

    const persistQueue = React.useCallback(() => {
        try {
            const { queue, index, repeatMode } = stateRef.current;
            if (queue.length === 0) {
                sessionStorage.removeItem(QUEUE_STORAGE_KEY);
                return;
            }
            sessionStorage.setItem(QUEUE_STORAGE_KEY, JSON.stringify({
                queue, index, repeatMode, elapsed: elapsedRef.current, duration: durationRef.current,
            } satisfies PersistedQueue));
        } catch {
            // storage full/unavailable — the queue just won't survive a reload
        }
    }, []);

    // Restore once on mount (client only), paused at the saved position.
    React.useEffect(() => {
        const saved = readPersistedQueue();
        if (!saved) return;
        const idx = Math.min(Math.max(saved.index, 0), saved.queue.length - 1);
        const track = saved.queue[idx];
        setQueue(saved.queue.map(stamp));
        setIndex(idx);
        setRepeatMode(saved.repeatMode ?? 'off');
        elapsedRef.current = saved.elapsed || 0;
        pendingSeekRef.current = saved.elapsed || 0;
        const trackDuration = saved.duration || Number(track.duration) || 0;
        durationRef.current = trackDuration;
        setTime({
            elapsed: saved.elapsed || 0,
            duration: trackDuration,
            progress: trackDuration ? (saved.elapsed || 0) / trackDuration : 0,
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    // Persist structural changes immediately; position is saved on a
    // throttle from timeupdate and on pagehide.
    React.useEffect(persistQueue, [queue, index, repeatMode, persistQueue]);
    React.useEffect(() => {
        window.addEventListener('pagehide', persistQueue);
        return () => window.removeEventListener('pagehide', persistQueue);
    }, [persistQueue]);

    const audio = () => {
        if (typeof window === 'undefined') return null;
        if (!audioRef.current) {
            const el = new Audio();
            el.preload = 'auto';
            el.volume = readVolumeCookie() / 100;
            el.addEventListener('timeupdate', () => {
                setTime({
                    elapsed: el.currentTime,
                    duration: el.duration || 0,
                    progress: el.duration ? el.currentTime / el.duration : 0,
                });
                elapsedRef.current = el.currentTime;
                if (el.duration) durationRef.current = el.duration;
                if (Date.now() - lastPersistRef.current > 5000) {
                    lastPersistRef.current = Date.now();
                    persistQueue();
                }
            });
            el.addEventListener('loadedmetadata', () => {
                if (pendingSeekRef.current > 0) {
                    el.currentTime = Math.min(pendingSeekRef.current, el.duration || pendingSeekRef.current);
                    pendingSeekRef.current = 0;
                }
            });
            el.addEventListener('progress', () => {
                try {
                    const b = el.buffered;
                    setTime({ buffered: b.length && el.duration ? b.end(b.length - 1) / el.duration : 0 });
                } catch {
                    // buffered ranges can be briefly unqueryable mid-load
                }
            });
            el.addEventListener('play', () => setIsPlaying(true));
            el.addEventListener('pause', () => setIsPlaying(false));
            el.addEventListener('ended', () => {
                const { queue, index, repeatMode } = stateRef.current;
                if (repeatMode === 'one') {
                    el.currentTime = 0;
                    void el.play();
                } else if (index + 1 < queue.length) {
                    loadAndPlay(queue, index + 1);
                } else if (repeatMode === 'queue' && queue.length > 0) {
                    loadAndPlay(queue, 0);
                }
            });
            audioRef.current = el;
            liveAudio = el;
        }
        return audioRef.current;
    };

    const broadcastMediaSession = (track: TrackSummary) => {
        if (typeof navigator === 'undefined' || !('mediaSession' in navigator)) return;
        navigator.mediaSession.metadata = new MediaMetadata({
            title: track.title,
            artist: track.user.name,
            album: track.album?.title ?? 'Pony.fm',
            artwork: [
                { src: track.covers.thumbnail, sizes: '50x50' },
                { src: track.covers.small, sizes: '100x100' },
                { src: track.covers.normal, sizes: '350x350' },
            ],
        });
        navigator.mediaSession.setActionHandler('play', () => void audioRef.current?.play());
        navigator.mediaSession.setActionHandler('pause', () => audioRef.current?.pause());
        navigator.mediaSession.setActionHandler('previoustrack', playPrev);
        navigator.mediaSession.setActionHandler('nexttrack', playNext);
        navigator.mediaSession.setActionHandler('seekto', (e) => {
            if (audioRef.current && e.seekTime != null) audioRef.current.currentTime = e.seekTime;
        });
    };

    const loadAndPlay = (tracks: QueueEntry[], i: number, seekTo = 0) => {
        const el = audio();
        const track = tracks[i];
        if (!el || !track) return;
        const src = pickSource(track, el);
        if (!src) return;
        pendingSeekRef.current = seekTo;
        elapsedRef.current = seekTo;
        setQueue(tracks);
        setIndex(i);
        setTime({
            progress: Number(track.duration) ? seekTo / Number(track.duration) : 0,
            elapsed: seekTo,
            buffered: 0,
            duration: Number(track.duration) || 0,
        });
        el.src = src;
        void el.play();
        broadcastMediaSession(track);
    };

    const current = queue[index] ?? null;

    const playTracks = (tracks: TrackSummary[], i = 0) => {
        const target = tracks[i];
        // Clicking the already-loaded track toggles play/pause.
        if (current && target && current.id === target.id) {
            playPause();
            return;
        }
        loadAndPlay(tracks.map(stamp), i);
    };

    const playPause = () => {
        const el = audio();
        if (!el || !current) return;
        // A restored session has queue state but no source loaded yet —
        // load the current entry and resume from the saved position.
        if (!el.src) {
            loadAndPlay(stateRef.current.queue, stateRef.current.index, elapsedRef.current);
            return;
        }
        if (el.paused) void el.play();
        else el.pause();
    };

    const queueNext = (track: TrackSummary) => {
        if (!current) {
            loadAndPlay([stamp(track)], 0);
            return;
        }
        setQueue((q) => {
            const next = [...q];
            next.splice(stateRef.current.index + 1, 0, stamp(track));
            return next;
        });
    };

    const queueLast = (track: TrackSummary) => {
        if (!current) {
            loadAndPlay([stamp(track)], 0);
            return;
        }
        setQueue((q) => [...q, stamp(track)]);
    };

    const playAt = (i: number) => {
        const { queue, index } = stateRef.current;
        if (!queue[i]) return;
        if (i === index) {
            playPause();
            return;
        }
        loadAndPlay(queue, i);
    };

    const moveInQueue = (from: number, to: number) => {
        if (from === to) return;
        setQueue((q) => {
            if (!q[from] || to < 0 || to >= q.length) return q;
            const next = [...q];
            const [moved] = next.splice(from, 1);
            next.splice(to, 0, moved);
            return next;
        });
        setIndex((idx) => {
            if (from === idx) return to;
            if (from < idx && to >= idx) return idx - 1;
            if (from > idx && to <= idx) return idx + 1;
            return idx;
        });
    };

    const removeFromQueue = (i: number) => {
        const { queue, index } = stateRef.current;
        if (!queue[i]) return;
        const next = queue.filter((_, n) => n !== i);
        if (i !== index) {
            setQueue(next);
            if (i < index) setIndex(index - 1);
            return;
        }
        if (next.length === 0) {
            const el = audioRef.current;
            if (el) {
                el.pause();
                el.removeAttribute('src');
            }
            setQueue([]);
            setIndex(0);
            setTime({ progress: 0, elapsed: 0, buffered: 0, duration: 0 });
            return;
        }
        // Removing the playing track: the next one takes its slot.
        loadAndPlay(next, Math.min(i, next.length - 1));
    };

    const playNext = () => {
        const { queue, index, repeatMode } = stateRef.current;
        if (index + 1 < queue.length) loadAndPlay(queue, index + 1);
        else if (repeatMode !== 'off' && queue.length > 0) loadAndPlay(queue, 0);
    };

    const playPrev = () => {
        const { queue, index, repeatMode } = stateRef.current;
        if (index > 0) loadAndPlay(queue, index - 1);
        else if (repeatMode !== 'off' && queue.length > 0) loadAndPlay(queue, queue.length - 1);
    };

    const seekTo = (fraction: number) => {
        const el = audio();
        if (!el || !el.duration) return;
        el.currentTime = fraction * el.duration;
        setTime({ progress: fraction });
    };

    const setVolume = (v: number) => {
        const clamped = Math.min(100, Math.max(0, Math.round(v)));
        setVolumeState(clamped);
        if (audioRef.current) audioRef.current.volume = clamped / 100;
        document.cookie = 'pfm-volume=' + clamped + ';path=/;max-age=31536000';
    };

    const toggleRepeat = () =>
        setRepeatMode((m) => (m === 'off' ? 'queue' : m === 'queue' ? 'one' : 'off'));

    const value: PlayerState = {
        queue,
        index,
        current,
        isPlaying,
        volume,
        repeatMode,
        playTracks,
        queueNext,
        queueLast,
        playAt,
        removeFromQueue,
        moveInQueue,
        playPause,
        playNext,
        playPrev,
        seekTo,
        setVolume,
        toggleRepeat,
        isCurrent: (id) => current != null && id != null && current.id === id,
    };

    return (
        <PlayerContext.Provider value={value}>
            <PlayerTimeContext.Provider value={timeStore}>{children}</PlayerTimeContext.Provider>
        </PlayerContext.Provider>
    );
}

export function usePlayer(): PlayerState {
    const ctx = React.useContext(PlayerContext);
    if (!ctx) throw new Error('usePlayer must be used within PlayerProvider');
    return ctx;
}

/** High-frequency playback position — subscribe only where it's rendered. */
export function usePlayerTime(): PlayerTime {
    const ctx = React.useContext(PlayerTimeContext);
    if (!ctx) throw new Error('usePlayerTime must be used within PlayerProvider');
    return React.useSyncExternalStore(ctx.subscribe, ctx.getSnapshot, ctx.getSnapshot);
}

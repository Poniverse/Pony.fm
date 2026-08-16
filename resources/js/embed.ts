/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2026 Feld0.
 *
 * The embeddable player (t{id}/embed) — a standalone bundle with no React.
 * HTML5 Audio replaces the old Flash-era SoundManager2 build.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

function ready(fn: () => void) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
}

function cookie(name: string): string | null {
    const m = document.cookie.match(new RegExp('(?:^|;\\s*)' + name + '=([^;]+)'));
    return m ? decodeURIComponent(m[1]) : null;
}

ready(() => {
    const player = document.querySelector<HTMLElement>('.player');
    if (!player) return;

    const trackId = player.dataset.trackId;
    const durationMs = parseFloat(player.dataset.duration ?? '0');
    const play = player.querySelector<HTMLElement>('.play');
    const progressBar = player.querySelector<HTMLElement>('.progressbar');
    const loadingBar = player.querySelector<HTMLElement>('.loader');
    const seekBar = player.querySelector<HTMLElement>('.seeker');
    const playIcon = play?.querySelector<HTMLElement>('.button i');

    let audio: HTMLAudioElement | null = null;

    player.classList.remove('loading');

    const setPlaying = (playing: boolean) => {
        player.classList.toggle('playing', playing);
        player.classList.toggle('paused', !playing);
        playIcon?.classList.toggle('fa-play', !playing);
        playIcon?.classList.toggle('fa-pause', playing);
    };

    const buildAudio = (): HTMLAudioElement => {
        const el = new Audio();
        el.volume = 0.5;
        const candidates: [string, string][] = [
            ['/t' + trackId + '/stream.mp3', 'audio/mpeg'],
            ['/t' + trackId + '/stream.ogg', 'audio/ogg; codecs="vorbis"'],
            ['/t' + trackId + '/stream.m4a', 'audio/mp4'],
        ];
        const source = candidates.find(([, mime]) => el.canPlayType(mime)) ?? candidates[0];
        el.src = source[0];
        el.addEventListener('timeupdate', () => {
            if (seekBar && el.duration) seekBar.style.width = (el.currentTime / el.duration) * 100 + '%';
        });
        el.addEventListener('progress', () => {
            if (!loadingBar || !el.duration) return;
            try {
                const b = el.buffered;
                if (b.length) loadingBar.style.width = (b.end(b.length - 1) / el.duration) * 100 + '%';
            } catch {
                // buffered ranges can be briefly unqueryable
            }
        });
        el.addEventListener('play', () => setPlaying(true));
        el.addEventListener('pause', () => setPlaying(false));
        el.addEventListener('ended', () => {
            setPlaying(false);
            player.classList.remove('playing', 'paused');
            if (seekBar) seekBar.style.width = '0';
            audio = null;
        });
        return el;
    };

    play?.addEventListener('click', () => {
        if (!audio) audio = buildAudio();
        if (audio.paused) void audio.play();
        else audio.pause();
    });

    progressBar?.addEventListener('click', (e) => {
        if (!audio) return;
        const rect = progressBar.getBoundingClientRect();
        const fraction = Math.min(1, Math.max(0, (e.clientX - rect.left) / rect.width));
        const duration = audio.duration || durationMs / 1000;
        if (duration) audio.currentTime = fraction * duration;
    });

    // Favourite star (rendered only for logged-in viewers).
    const favourite = player.querySelector<HTMLAnchorElement>('.favourite');
    favourite?.addEventListener('click', (e) => {
        e.preventDefault();
        const xsrf = cookie('XSRF-TOKEN');
        void fetch('/api/web/favourites/toggle', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(xsrf ? { 'X-XSRF-TOKEN': xsrf } : {}),
            },
            body: JSON.stringify({ type: 'track', id: Number(trackId) }),
        })
            .then((r) => r.json())
            .then((res: { is_favourited?: boolean }) => {
                player.classList.toggle('favourited', !!res.is_favourited);
            })
            .catch(() => undefined);
    });
});

import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Area, AreaChart, CartesianGrid, XAxis, YAxis } from 'recharts';
import AppLayout from '@/layouts/AppLayout';
import { SectionHeader } from '@/design-system/feedback/SectionHeader';
import { Tabs } from '@/design-system/navigation/Tabs';
import { AlbumArt } from '@/design-system/music/AlbumArt';
import { ChartContainer, ChartTooltip, ChartTooltipContent, type ChartConfig } from '@/design-system/primitives/chart';
import { api } from '@/lib/api';
import type { TrackSummary } from '@/lib/types';

interface PlayStat { plays: number; hours?: string; days?: string }

const chartConfig = {
    plays: {
        label: 'Plays',
        color: 'var(--purple-400)',
    },
} satisfies ChartConfig;

export default function TrackStatsPage({ track }: { track: TrackSummary }) {
    const [stats, setStats] = React.useState<{ playStats: PlayStat[]; type: 'Hourly' | 'Daily' } | null>(null);
    const [mode, setMode] = React.useState<'cumulative' | 'daily'>('cumulative');

    React.useEffect(() => {
        api.get<{ playStats: PlayStat[]; type: 'Hourly' | 'Daily' }>(`/tracks/${track.id}/stats`)
            .then(({ data }) => setStats(data))
            .catch(() => undefined);
    }, [track.id]);

    const data = React.useMemo(() => {
        let total = 0;
        return (stats?.playStats ?? []).map((s) => {
            total += s.plays;
            return {
                period: s.hours ?? s.days ?? '',
                plays: mode === 'cumulative' ? total : s.plays,
            };
        });
    }, [stats, mode]);

    return (
        <div className="grid max-w-[900px] gap-5 px-7 pt-6 pb-12">
            <Head title={track.title + ' - Stats'} />
            <div className="flex items-center gap-3.5">
                <AlbumArt src={track.covers.small} alt={track.title} size="md" />
                <div className="grid gap-[3px]">
                    <SectionHeader title={'Play stats — ' + track.title} />
                    <span className="text-sm text-muted-foreground">
                        by <Link href={track.user.url}>{track.user.name}</Link>
                        {stats ? ' · ' + stats.type.toLowerCase() + ' buckets' : ''}
                    </span>
                </div>
            </div>

            <Tabs active={mode} onChange={(id) => setMode(id as 'cumulative' | 'daily')} tabs={[
                { id: 'cumulative', label: 'Cumulative' },
                { id: 'daily', label: 'Per period' },
            ]} />

            {stats ? (
                <ChartContainer config={chartConfig} className="max-h-[380px] w-full">
                    <AreaChart data={data} margin={{ top: 8, right: 8 }}>
                        <CartesianGrid vertical={false} />
                        <XAxis dataKey="period" tickLine={false} axisLine={false} tickMargin={8} minTickGap={24} />
                        <YAxis tickLine={false} axisLine={false} tickMargin={8} width={40} allowDecimals={false} />
                        <ChartTooltip cursor={false} content={<ChartTooltipContent indicator="line" />} />
                        <Area
                            dataKey="plays"
                            type="monotone"
                            fill="var(--color-plays)"
                            fillOpacity={0.18}
                            stroke="var(--color-plays)"
                            strokeWidth={2}
                            dot={false}
                        />
                    </AreaChart>
                </ChartContainer>
            ) : (
                <p className="m-0 text-sm text-muted-foreground">Loading play data…</p>
            )}
        </div>
    );
}

TrackStatsPage.layout = (page: React.ReactNode) => <AppLayout children={page} />;

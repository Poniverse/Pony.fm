import { useCallback, useEffect, useState } from 'react';
import { api } from '@/lib/api';
import type { NotificationData } from '@/lib/types';

const POLL_INTERVAL = 60_000; // the old pullout refreshed every 60s

export function useNotifications(enabled: boolean) {
    const [notifications, setNotifications] = useState<NotificationData[]>([]);

    const refresh = useCallback(() => {
        if (!enabled) return;
        api.get<{ notifications: NotificationData[] }>('/notifications')
            .then(({ data }) => setNotifications(Array.isArray(data?.notifications) ? data.notifications : []))
            .catch(() => undefined);
    }, [enabled]);

    useEffect(() => {
        if (!enabled) return;
        refresh();
        const h = setInterval(refresh, POLL_INTERVAL);
        return () => clearInterval(h);
    }, [enabled, refresh]);

    const unreadCount = notifications.filter((n) => !n.is_read).length;

    const markAllAsRead = useCallback(() => {
        const unreadIds = notifications.filter((n) => !n.is_read).map((n) => n.id);
        if (unreadIds.length === 0) return;
        setNotifications((list) => list.map((n) => ({ ...n, is_read: true })));
        void api.put('/notifications/mark-as-read', { notification_ids: unreadIds }).catch(() => undefined);
    }, [notifications]);

    return { notifications, unreadCount, refresh, markAllAsRead };
}

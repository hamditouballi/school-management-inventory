/**
 * Notification Inbox System
 * Handles real-time polling, sound, and dropdown notifications
 */

const NotificationInbox = {
    pollingInterval: 30000,
    currentCount: 0,
    audio: null,
    pollingTimer: null,
    isAuthenticated: false,
    hasUserInteracted: false,

    init() {
        this.checkAuth();
        this.initAudio();
        this.setupUserInteraction();
        this.startPolling();
        this.setupEventListeners();
    },

    checkAuth() {
        this.isAuthenticated = document.body.dataset.auth === 'true';
    },

    initAudio() {
        this.audio = new Audio('/songs/bell.wav');
        this.audio.volume = 0.5;
    },

    setupUserInteraction() {
        const enableSound = (e) => {
            if (this.hasUserInteracted) return;
            
            this.hasUserInteracted = true;
            
            // Play sound DIRECTLY inside the event handler (this is key for autoplay policy)
            if (this.currentCount > 0 && this.audio) {
                this.audio.currentTime = 0;
                this.audio.play().catch(() => {});
            }
            
            document.removeEventListener('click', enableSound);
            document.removeEventListener('keydown', enableSound);
            document.removeEventListener('scroll', enableSound);
        };
        
        document.addEventListener('click', enableSound);
        document.addEventListener('keydown', enableSound);
        document.addEventListener('scroll', enableSound);
    },

    playSound() {
        if (!this.hasUserInteracted || !this.audio) return;
        
        this.audio.currentTime = 0;
        this.audio.play().catch(() => {});
    },

    startPolling() {
        if (!this.isAuthenticated) return;

        this.fetchUnreadCount();
        this.pollingTimer = setInterval(() => {
            this.fetchUnreadCount();
        }, this.pollingInterval);
    },

    stopPolling() {
        if (this.pollingTimer) {
            clearInterval(this.pollingTimer);
            this.pollingTimer = null;
        }
    },

    async fetchUnreadCount() {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            const response = await fetch('/api/notifications/unread-count', {
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || ''
                }
            });

            if (!response.ok) return;

            const data = await response.json();

            if (data.count > 0 && (data.count > this.currentCount || (this.currentCount === 0 && data.count > 0))) {
                this.playSound();
            }

            this.currentCount = data.count;
            this.updateBadge(data.count);
            this.updateDropdown();
        } catch (error) {
            console.error('[Notifications] Error:', error);
        }
    },

    updateBadge(count) {
        const badge = document.getElementById('notification-badge');
        if (badge) {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }
    },

    async updateDropdown() {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            const response = await fetch('/api/notifications/recent', {
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || ''
                }
            });

            if (!response.ok) return;

            const notifications = await response.json();
            const container = document.getElementById('notification-list');
            if (!container) return;

            if (notifications.length === 0) {
                container.innerHTML = `
                    <div class="px-4 py-6 text-center text-gray-500 text-sm">
                        <p>No notifications</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = notifications.map(n => `
                <div class="px-4 py-3 hover:bg-gray-50 transition cursor-pointer border-b border-gray-100 last:border-0 ${!n.read_at ? 'bg-blue-50' : ''}"
                     onclick="NotificationInbox.handleNotificationClick('${n.id}', '${n.data.url || '/notifications'}')">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center ${this.getIconClass(n.data.type)}">
                            ${this.getIcon(n.data.type)}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-900 line-clamp-2">${n.data.message}</p>
                            <p class="text-xs text-gray-500 mt-1">${this.formatTimeAgo(n.created_at)}</p>
                        </div>
                        ${!n.read_at ? '<span class="flex-shrink-0 w-2 h-2 bg-green-500 rounded-full mt-2"></span>' : ''}
                    </div>
                </div>
            `).join('');
        } catch (error) {
            console.error('[Notifications] Error:', error);
        }
    },

    getIconClass(type) {
        switch(type) {
            case 'request_approved':
            case 'other_hr_approved':
            case 'new_approved_request':
                return 'bg-green-500';
            case 'request_rejected':
            case 'request_expired':
                return 'bg-red-500';
            case 'request_needs_approval':
                return 'bg-blue-500';
            default:
                return 'bg-gray-500';
        }
    },

    getIcon(type) {
        const icons = {
            'request_needs_approval': '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>',
            'request_approved': '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>',
            'request_rejected': '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>',
            'other_hr_approved': '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
            'new_approved_request': '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
            'request_expired': '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
        };
        return icons[type] || icons['request_needs_approval'];
    },

    formatTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);

        if (diffMins < 1) return 'Just now';
        if (diffMins < 60) return diffMins + 'm ago';
        if (diffHours < 24) return diffHours + 'h ago';
        if (diffDays < 7) return diffDays + 'd ago';

        return date.toLocaleDateString();
    },

    async handleNotificationClick(id, url) {
        try {
            await fetch(`/api/notifications/${id}/read`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                }
            });
        } catch (error) {
            console.error('[Notifications] Error:', error);
        }
        
        window.location.href = url;
    },

    async markAllAsRead() {
        try {
            await fetch('/api/notifications/read-all', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                }
            });
            this.updateDropdown();
            this.fetchUnreadCount();
        } catch (error) {
            console.error('[Notifications] Error:', error);
        }
    },

    setupEventListeners() {
        document.addEventListener('notification-read', () => {
            this.fetchUnreadCount();
        });

        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                this.fetchUnreadCount();
            }
        });
    }
};

document.addEventListener('DOMContentLoaded', () => {
    NotificationInbox.init();
});

window.NotificationInbox = NotificationInbox;

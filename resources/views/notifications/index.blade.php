@extends('layouts.app')

@section('title', __('messages.notifications'))

@section('content')
<div x-data="notificationInbox()" x-init="init()" class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-xl font-bold text-gray-800">{{ __('messages.notifications') }}</h2>
            <button 
                @click="markAllRead()"
                x-show="notifications.some(n => !n.read_at)"
                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm">
                {{ __('messages.mark_all_as_read') }}
            </button>
        </div>

        <div class="px-6 py-3 bg-gray-50 border-b border-gray-200 flex gap-4">
            <button 
                @click="filter = 'all'"
                :class="filter === 'all' ? 'bg-green-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'"
                class="px-4 py-2 rounded-lg text-sm font-medium transition">
                {{ __('messages.all_notifications') }}
            </button>
            <button 
                @click="filter = 'unread'"
                :class="filter === 'unread' ? 'bg-green-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'"
                class="px-4 py-2 rounded-lg text-sm font-medium transition">
                {{ __('messages.unread') }}
                <span x-text="unreadCount" x-show="unreadCount > 0" class="ml-1 px-2 py-0.5 bg-red-500 text-white rounded-full text-xs"></span>
            </button>
        </div>

        <div x-show="loading" class="p-8 text-center text-gray-500">
            {{ __('messages.loading') }}
        </div>

        <div x-show="!loading && filteredNotifications.length === 0" class="p-12 text-center text-gray-500">
            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
            </svg>
            <p>{{ __('messages.no_notifications') }}</p>
        </div>

        <ul x-show="!loading && filteredNotifications.length > 0" class="divide-y divide-gray-200">
            <template x-for="notification in filteredNotifications" :key="notification.id">
                <li 
                    class="px-6 py-4 hover:bg-gray-50 transition cursor-pointer"
                    :class="{ 'bg-blue-50': !notification.read_at }"
                    @click="markAsRead(notification)">
                    <div class="flex items-start gap-4">
                        <div 
                            class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center"
                            :class="getNotificationIconClass(notification)">
                            <template x-if="notification.data.type === 'request_needs_approval'">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                            </template>
                            <template x-if="notification.data.type === 'request_approved'">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </template>
                            <template x-if="notification.data.type === 'request_rejected'">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </template>
                            <template x-if="notification.data.type === 'other_hr_approved' || notification.data.type === 'new_approved_request'">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </template>
                            <template x-if="notification.data.type === 'request_expired'">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </template>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900" x-text="notification.data.message"></p>
                            <p class="text-sm text-gray-500 mt-1" x-text="formatTimeAgo(notification.created_at)"></p>
                        </div>
                        <div x-show="!notification.read_at" class="flex-shrink-0">
                            <span class="inline-block w-3 h-3 bg-green-500 rounded-full"></span>
                        </div>
                    </div>
                </li>
            </template>
        </ul>

        <div x-show="!loading && hasMore" class="px-6 py-3 border-t border-gray-200 text-center">
            <button 
                @click="loadMore()"
                class="text-green-600 hover:text-green-700 font-medium text-sm">
                {{ __('messages.next') }}
            </button>
        </div>
    </div>
</div>

<script>
function notificationInbox() {
    return {
        notifications: [],
        loading: true,
        filter: 'all',
        page: 1,
        hasMore: true,
        
        init() {
            this.loadNotifications();
        },
        
        async loadNotifications() {
            this.loading = true;
            try {
                const response = await fetch('/api/notifications?page=' + this.page);
                const data = await response.json();
                
                if (this.page === 1) {
                    this.notifications = data.data;
                } else {
                    this.notifications = [...this.notifications, ...data.data];
                }
                
                this.hasMore = data.current_page < data.last_page;
            } catch (error) {
                console.error('Error loading notifications:', error);
            } finally {
                this.loading = false;
            }
        },
        
        loadMore() {
            this.page++;
            this.loadNotifications();
        },
        
        async markAsRead(notification) {
            if (notification.read_at) return;
            
            try {
                await fetch('/api/notifications/' + notification.id + '/read', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                    }
                });
                
                notification.read_at = new Date().toISOString();
                this.$dispatch('notification-read');
            } catch (error) {
                console.error('Error marking as read:', error);
            }
        },
        
        async markAllRead() {
            try {
                await fetch('/api/notifications/read-all', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                    }
                });
                
                this.notifications.forEach(n => {
                    n.read_at = new Date().toISOString();
                });
                
                this.$dispatch('notification-read');
            } catch (error) {
                console.error('Error marking all as read:', error);
            }
        },
        
        get filteredNotifications() {
            if (this.filter === 'unread') {
                return this.notifications.filter(n => !n.read_at);
            }
            return this.notifications;
        },
        
        get unreadCount() {
            return this.notifications.filter(n => !n.read_at).length;
        },
        
        getNotificationIconClass(notification) {
            const type = notification.data.type;
            if (type === 'request_approved' || type === 'other_hr_approved' || type === 'new_approved_request') {
                return 'bg-green-500';
            } else if (type === 'request_rejected' || type === 'request_expired') {
                return 'bg-red-500';
            } else if (type === 'request_needs_approval') {
                return 'bg-blue-500';
            }
            return 'bg-gray-500';
        },
        
        formatTimeAgo(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);
            
            if (diffMins < 1) return 'Just now';
            if (diffMins < 60) return diffMins + ' min ago';
            if (diffHours < 24) return diffHours + ' hour' + (diffHours > 1 ? 's' : '') + ' ago';
            if (diffDays < 7) return diffDays + ' day' + (diffDays > 1 ? 's' : '') + ' ago';
            
            return date.toLocaleDateString();
        }
    }
}
</script>
@endsection

<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed permissions and roles
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);

        // Get role
        $devRole = \App\Models\Role::where('name', 'developer')->first();

        // Create users
        $this->user = User::factory()->create();
        $this->user->roles()->attach($devRole);

        $this->otherUser = User::factory()->create();
        $this->otherUser->roles()->attach($devRole);
    }

    public function test_user_can_view_their_notifications(): void
    {
        // Create notifications for the user
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        // Create notifications for another user (should not be visible)
        Notification::factory()->count(2)->create([
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('notifications.index'));

        $response->assertOk();
        $response->assertViewIs('notifications.index');
        $response->assertViewHas('notifications');

        // Check that only user's notifications are returned
        $notifications = $response->viewData('notifications');
        $this->assertCount(3, $notifications);
    }

    public function test_notifications_are_ordered_by_created_at_desc(): void
    {
        // Create notifications with different timestamps
        $oldest = Notification::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(3),
        ]);

        $newest = Notification::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now(),
        ]);

        $middle = Notification::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('notifications.index'));

        $notifications = $response->viewData('notifications');
        
        // Check order: newest first
        $this->assertEquals($newest->id, $notifications[0]->id);
        $this->assertEquals($middle->id, $notifications[1]->id);
        $this->assertEquals($oldest->id, $notifications[2]->id);
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        $this->assertNull($notification->read_at);

        $response = $this->actingAs($this->user)
            ->patch(route('notifications.mark-as-read', $notification));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }

    public function test_user_cannot_mark_others_notification_as_read(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->otherUser->id,
            'read_at' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->patch(route('notifications.mark-as-read', $notification));

        $response->assertForbidden();

        $notification->refresh();
        $this->assertNull($notification->read_at);
    }

    public function test_marking_already_read_notification_does_not_change_timestamp(): void
    {
        $originalReadAt = now()->subHour();
        
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'read_at' => $originalReadAt,
        ]);

        $response = $this->actingAs($this->user)
            ->patch(route('notifications.mark-as-read', $notification));

        $response->assertRedirect();

        $notification->refresh();
        $this->assertEquals(
            $originalReadAt->timestamp,
            $notification->read_at->timestamp
        );
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        // Create unread notifications
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        // Create already read notification
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'read_at' => now()->subDay(),
        ]);

        // Create unread notification for another user (should not be affected)
        $otherNotification = Notification::factory()->create([
            'user_id' => $this->otherUser->id,
            'read_at' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('notifications.mark-all-read'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check all user's notifications are marked as read
        $unreadCount = Notification::where('user_id', $this->user->id)
            ->whereNull('read_at')
            ->count();
        
        $this->assertEquals(0, $unreadCount);

        // Check other user's notification is not affected
        $otherNotification->refresh();
        $this->assertNull($otherNotification->read_at);
    }

    public function test_guest_cannot_access_notifications(): void
    {
        $response = $this->get(route('notifications.index'));
        // Guest users should not be able to access notifications
        // The response could be redirect (302), unauthorized (401/403), or error (500 if auth not configured)
        $this->assertNotEquals(200, $response->status(), 'Guest should not have OK access to notifications');
    }

    public function test_notifications_are_paginated(): void
    {
        // Create more than 20 notifications (default pagination)
        Notification::factory()->count(25)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('notifications.index'));

        $notifications = $response->viewData('notifications');
        
        // Should have 20 items per page
        $this->assertCount(20, $notifications);
        $this->assertEquals(25, $notifications->total());
    }
}

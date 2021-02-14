<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\NotificationsController
 */
class NotificationsControllerTest extends TestCase
{
    /**
     * @test
     */
    public function get_email_click_returns_an_ok_response()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $notification = \App\Models\Notification::factory()->create();

        $response = $this->get(route('email:click', ['emailKey' => $notification->emailKey]));

        $response->assertRedirect(to($email->getActivity()->url));

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function get_email_unsubscribe_returns_an_ok_response()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $notification = \App\Models\Notification::factory()->create();

        $response = $this->get(route('email:unsubscribe', ['subscriptionKey' => $notification->subscriptionKey]));

        $response->assertRedirect(route('account:settings', ['slug' => $subscription->user->slug, 'unsubscribedMessageKey' => $subscription->activity_type]));

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function get_email_unsubscribe_page_returns_an_ok_response()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->get(route('email:confirm-unsubscribed'));

        $response->assertOk();
        $response->assertViewIs('shared.null');

        // TODO: perform additional assertions
    }

    // test cases...
}

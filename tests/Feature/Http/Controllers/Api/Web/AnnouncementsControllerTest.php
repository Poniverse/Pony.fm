<?php

namespace Tests\Feature\Http\Controllers\Api\Web;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Api\Web\AnnouncementsController
 */
class AnnouncementsControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function get_admin_index_returns_an_ok_response()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->get('api/web/admin/announcements');

        $response->assertOk();

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function get_index_returns_an_ok_response()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->get('api/web/announcements');

        $response->assertOk();

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function get_item_by_id_returns_an_ok_response()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $announcement = \App\Models\Announcement::factory()->create();

        $response = $this->get('api/web/admin/announcements/{id}');

        $response->assertOk();

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function post_create_returns_an_ok_response()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->post('api/web/admin/announcements', [
            // TODO: send request data
        ]);

        $response->assertOk();

        // TODO: perform additional assertions
    }

    // test cases...
}

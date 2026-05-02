<?php

namespace Tests\Feature;

use App\Models\PersonalFile;
use App\Models\PersonalFolder;
use App\Models\PersonalFolderShare;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnlineDocsAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_personal_root_file_is_visible_to_owner_only(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $file = PersonalFile::query()->create([
            'user_id' => $owner->id,
            'folder_id' => null,
            'stored_path' => 'personal-files/' . $owner->id . '/root/test.txt',
            'original_name' => 'test.txt',
            'mime_type' => 'text/plain',
            'size' => 12,
        ]);

        $this->actingAs($intruder)
            ->get(route('online-docs.files.download', ['file' => $file->id]))
            ->assertStatus(403);

        // Owner is authorized; missing fixture file should yield 404, not 403.
        $this->actingAs($owner)
            ->get(route('online-docs.files.download', ['file' => $file->id]))
            ->assertStatus(404);
    }

    public function test_user_with_folder_share_can_access_file_in_that_folder(): void
    {
        $owner = User::factory()->create();
        $sharedUser = User::factory()->create();

        $folder = PersonalFolder::query()->create([
            'user_id' => $owner->id,
            'parent_id' => null,
            'name' => 'Shared Folder',
            'share_token' => null,
            'share_link_enabled' => false,
        ]);

        PersonalFolderShare::query()->create([
            'folder_id' => $folder->id,
            'user_id' => $sharedUser->id,
            'shared_by' => $owner->id,
            'permission' => 'view',
        ]);

        $file = PersonalFile::query()->create([
            'user_id' => $owner->id,
            'folder_id' => $folder->id,
            'stored_path' => 'personal-files/' . $owner->id . '/folder-' . $folder->id . '/shared.docx',
            'original_name' => 'shared.docx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'size' => 120,
        ]);

        // Shared user is authorized; missing fixture file should yield 404, not 403.
        $this->actingAs($sharedUser)
            ->get(route('online-docs.files.download', ['file' => $file->id]))
            ->assertStatus(404);
    }

    public function test_share_link_does_not_auto_grant_access_for_unshared_user(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $folder = PersonalFolder::query()->create([
            'user_id' => $owner->id,
            'parent_id' => null,
            'name' => 'Token Folder',
            'share_token' => 'token-' . uniqid('', true),
            'share_link_enabled' => true,
        ]);

        $this->actingAs($intruder)
            ->get(route('online-docs.folders.share.open', ['token' => $folder->share_token]))
            ->assertRedirect(route('online-docs.home'))
            ->assertSessionHas('storage_error');

        $this->assertDatabaseMissing('personal_folder_shares', [
            'folder_id' => $folder->id,
            'user_id' => $intruder->id,
        ]);
    }

    public function test_share_link_works_for_user_already_shared_to_folder(): void
    {
        $owner = User::factory()->create();
        $sharedUser = User::factory()->create();

        $folder = PersonalFolder::query()->create([
            'user_id' => $owner->id,
            'parent_id' => null,
            'name' => 'Token Folder',
            'share_token' => 'token-' . uniqid('', true),
            'share_link_enabled' => true,
        ]);

        PersonalFolderShare::query()->create([
            'folder_id' => $folder->id,
            'user_id' => $sharedUser->id,
            'shared_by' => $owner->id,
            'permission' => 'view',
        ]);

        $this->actingAs($sharedUser)
            ->get(route('online-docs.folders.share.open', ['token' => $folder->share_token]))
            ->assertRedirect(route('online-docs.home', ['folder' => $folder->id]));

        $this->assertDatabaseHas('personal_folder_shares', [
            'folder_id' => $folder->id,
            'user_id' => $sharedUser->id,
        ]);
    }
}

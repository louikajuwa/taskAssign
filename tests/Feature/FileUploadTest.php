<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function upload(array $files, ?User $as = null)
    {
        return $this->actingAs($as ?? User::factory()->create())->post('/api/files/upload', ['files' => $files], ['Accept' => 'application/json']);
    }

    public function test_valid_pdf_and_png_are_stored_with_random_names(): void
    {
        $res = $this->upload([
            UploadedFile::fake()->create('my report.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->image('shot.png'),
        ])->assertOk()->assertJson(['result' => 'success']);

        $urls = $res->json('urls');
        $this->assertCount(2, $urls);
        foreach ($urls as $url) {
            $this->assertMatchesRegularExpression('#^/storage/uploads/[A-Za-z0-9]{40}\.(pdf|png)$#', $url);
            Storage::disk('public')->assertExists(str_replace('/storage/', '', $url));
            $this->assertStringNotContainsString('report', $url);
        }
    }

    public function test_dangerous_types_are_rejected(): void
    {
        foreach (['shell.php', 'logo.svg', 'page.html', 'run.phtml', 'x.exe', 'a.js'] as $name) {
            $this->upload([UploadedFile::fake()->create($name, 5)])->assertStatus(422);
        }
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_php_content_disguised_as_png_is_rejected(): void
    {
        // A real (non-fake) file so content sniffing is exercised.
        $path = tempnam(sys_get_temp_dir(), 'up');
        file_put_contents($path, '<?php system($_GET["c"]); ?>');
        $file = new UploadedFile($path, 'evil.png', null, null, true);

        $this->upload([$file])->assertStatus(422);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_stored_extension_comes_from_content_not_client_name(): void
    {
        $url = $this->upload([UploadedFile::fake()->image('evil.php.png')])->assertOk()->json('urls.0');

        $this->assertMatchesRegularExpression('#^/storage/uploads/[A-Za-z0-9]{40}\.png$#', $url);
    }

    public function test_oversized_file_is_rejected(): void
    {
        $this->upload([UploadedFile::fake()->create('big.pdf', 20481, 'application/pdf')])->assertStatus(422);
        $this->upload([UploadedFile::fake()->create('ok.pdf', 20480, 'application/pdf')])->assertOk();
    }

    public function test_more_than_ten_files_rejected(): void
    {
        $files = [];
        for ($i = 0; $i < 11; $i++) {
            $files[] = UploadedFile::fake()->create("f{$i}.pdf", 1, 'application/pdf');
        }

        $this->upload($files)->assertStatus(422)->assertJsonValidationErrors('files');
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_exactly_ten_files_allowed_and_none_fails(): void
    {
        $files = [];
        for ($i = 0; $i < 10; $i++) {
            $files[] = UploadedFile::fake()->create("f{$i}.pdf", 1, 'application/pdf');
        }
        $this->upload($files)->assertOk();

        $this->actingAs(User::factory()->create())->postJson('/api/files/upload', [])->assertStatus(422);
    }

    public function test_guest_is_rejected(): void
    {
        $this->post('/api/files/upload', ['files' => [UploadedFile::fake()->create('a.pdf', 1)]], ['Accept' => 'application/json'])
            ->assertUnauthorized();
        $this->assertSame([], Storage::disk('public')->allFiles());
    }
}

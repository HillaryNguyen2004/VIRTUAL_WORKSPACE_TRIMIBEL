<?php
namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserImportService
{
    public function importFromCsv(UploadedFile $file): int
    {
        $path = $file->getRealPath();
        $handle = fopen($path, 'r');
        $count = 0;

        try {
            $header = fgetcsv($handle); // skip header

            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 4) continue;

                [$name, $email, $password, $roles] = array_map('trim', $row);

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }

                if (User::where('email', $email)->exists()) {
                    continue;
                }

                $user = User::create([
                    'name'     => $name,
                    'email'    => $email,
                    'password' => Hash::make($password ?: Str::random(10)),
                    'roles'    => $roles ?: 'user',
                    'username' => $this->generateUniqueUsername(),
                ]);

                if (in_array($roles, ['admin', 'staff', 'user'])) {
                    $user->assignRole($roles);
                }

                $count++;
            }
        } finally {
            fclose($handle);
        }

        return $count;
    }

    public function downloadTemplate()
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="user_import_template.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['name', 'email', 'password', 'roles']);
            fputcsv($file, ['John Doe', 'john@example.com', 'password123', 'user']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Generate a unique alphanumeric username
     */
    private function generateUniqueUsername(): string
    {
        do {
            // Option A: Random alphanumeric, uppercase
            $username = Str::upper(Str::random(8));

            // Option B: Sequential "userX"
            // $username = 'user' . (User::max('id') + 1);

        } while (User::where('username', $username)->exists());

        return $username;
    }
}

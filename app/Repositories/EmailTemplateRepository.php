<?php
namespace App\Repositories;

use App\Models\EmailTemplate;

class EmailTemplateRepository
{
    public function create(array $data): EmailTemplate
    {
        return EmailTemplate::create($data);
    }

    public function update(EmailTemplate $template, array $data): bool
    {
        return $template->update($data);
    }

    public function delete(EmailTemplate $template): ?bool
    {
        return $template->delete();
    }

    public function paginate(int $perPage = 3)
    {
        return EmailTemplate::latest()->paginate($perPage);
    }
}

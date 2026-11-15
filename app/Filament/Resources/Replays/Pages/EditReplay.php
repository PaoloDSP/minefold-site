<?php

namespace App\Filament\Resources\Replays\Pages;

use App\Filament\Resources\Replays\ReplayResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReplay extends EditRecord
{
    protected static string $resource = ReplayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

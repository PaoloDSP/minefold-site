<?php

namespace App\Filament\Resources\Replays\Pages;

use App\Filament\Resources\Replays\ReplayResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReplays extends ListRecords
{
    protected static string $resource = ReplayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

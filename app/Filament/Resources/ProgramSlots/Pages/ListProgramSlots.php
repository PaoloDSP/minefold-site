<?php

namespace App\Filament\Resources\ProgramSlots\Pages;

use App\Filament\Resources\ProgramSlots\ProgramSlotResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProgramSlots extends ListRecords
{
    protected static string $resource = ProgramSlotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\ProgramSlots\Pages;

use App\Filament\Resources\ProgramSlots\ProgramSlotResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProgramSlot extends EditRecord
{
    protected static string $resource = ProgramSlotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

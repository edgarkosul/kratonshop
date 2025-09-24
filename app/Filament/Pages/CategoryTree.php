<?php

namespace App\Filament\Pages;

use App\Models\Category;
use Illuminate\Database\Eloquent\Model;
use SolutionForest\FilamentTree\Pages\TreePage;

class CategoryTree extends TreePage
{
    protected static string $model = Category::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static int $maxDepth = 3;

    protected static ?string $title = 'Категории';

    protected function getTreeToolbarActions(): array
    {
        return [];
    }

    protected function getActions(): array
    {
        return [
            $this->getCreateAction(),
            // SAMPLE CODE, CAN DELETE
            //\Filament\Pages\Actions\Action::make('sampleAction'),
        ];
    }

    protected function getFormSchema(): array
    {
    return [
        \Filament\Forms\Components\TextInput::make('name')->label('Название')->required(),
        \Filament\Forms\Components\TextInput::make('slug')->required(),
        \Filament\Forms\Components\TextInput::make('img'),
        \Filament\Forms\Components\Toggle::make('is_active')->label('Активна')->default(true),
    ];
    }

    protected function hasDeleteAction(): bool
    {
        return false;
    }

    protected function hasEditAction(): bool
    {
        return true;
    }

    protected function hasViewAction(): bool
    {
        return false;
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    public function getTreeRecordTitle(?Model $record = null): string
    {
        if (!$record) return '';

        return "{$record->name}";
    }

    public function getTreeRecordIcon(?\Illuminate\Database\Eloquent\Model $record = null): ?string
    {
        if ($record->parent_id != -1) {
            return null; // No icon for child records
        }

        return match ($record->name) {
            'Categories' => 'heroicon-o-tag',
            'Products' => 'heroicon-o-shopping-bag',
            'Settings' => 'heroicon-o-cog',
            default => 'heroicon-o-folder',
        };
    }

    // CUSTOMIZE ICON OF EACH RECORD, CAN DELETE
    // public function getTreeRecordIcon(?\Illuminate\Database\Eloquent\Model $record = null): ?string
    // {
    //     return null;
    // }
}

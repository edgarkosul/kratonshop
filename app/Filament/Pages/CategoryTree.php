<?php

namespace App\Filament\Pages;

use App\Models\Category;
use Illuminate\Support\Str;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Illuminate\Filesystem\FilesystemAdapter;
use Intervention\Image\Drivers\Imagick\Driver;
use SolutionForest\FilamentTree\Actions\Action;
use SolutionForest\FilamentTree\Pages\TreePage;
use SolutionForest\FilamentTree\Actions\EditAction;
use SolutionForest\FilamentTree\Actions\CreateAction;
use SolutionForest\FilamentTree\Actions\DeleteAction;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

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
            // Родитель
            Select::make('parent_id')
                ->label('Родительская категория')
                ->options(fn() => $this->categoryOptions())   // см. helper ниже
                ->default(-1)
                ->searchable()
                ->preload()
                ->required(),

            // Поля категории
            TextInput::make('name')
                ->label('Название')
                ->required()
                ->live(onBlur: true),

            TextInput::make('slug')
                ->label('Slug')
                ->required(),

            TextInput::make('img')
                ->label('Img'),

            \Filament\Forms\Components\Toggle::make('is_active')
                ->label('Активна')
                ->default(true),
        ];
    }

    protected function hasDeleteAction(): bool
    {
        return true;
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

    protected function getTreeActions(): array
    {
        return [
            // Создать ПОД выбранной категорией
            CreateAction::make('createCategory')
                ->label('')
                ->icon('heroicon-o-folder-plus')
                ->iconButton()
                ->schema([
                    Hidden::make('parent_id'),
                    ...$this->baseCategoryFields(),
                ])
                ->mutateDataUsing(function (array $data, Category $record) {
                    $data['parent_id'] = $record->id; // фиксируем родителя
                    return $data;
                }),
            // Редактировать
            $this->configureEditAction(
                EditAction::make()->schema($this->getEditFormSchema())
            ),


            DeleteAction::make()
                ->label('Удалить')
                ->icon('heroicon-o-trash')
                // ->visible(fn(Category $record) => $record->parent_id !== -1)
                ->requiresConfirmation()
                ->modalHeading('Удаление категории')
                ->modalDescription(function (Category $record) {
                    $children = Category::where('parent_id', $record->id)->count();
                    return $children > 0
                        ? "Будет удалена «{$record->name}» и все её дочерние категории ({$children}). Продолжить?"
                        : "Удалить «{$record->name}»?";
                })
                ->modalSubmitActionLabel('Да, удалить'),
        ];
    }

    protected function baseCategoryFields(): array
    {
        return [
            TextInput::make('name')
                ->label('Название')
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(fn($state, $set) => $set('slug', Str::slug($state ?? '', '-'))),

            TextInput::make('slug')->label('Slug')->required(),

            FileUpload::make('img')
                ->label('Изображение')
                ->image()                    // превью в Filament
                ->openable()
                ->downloadable()
                ->disk('public')             // будет искать и показывать через Storage::url()
                ->directory('categories')
                ->preserveFilenames()
                ->getUploadedFileNameForStorageUsing(
                    fn(TemporaryUploadedFile $file, $record): string =>
                    Str::slug(($record?->slug ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)), '-')
                        . '.webp'
                )
                ->saveUploadedFileUsing(function (TemporaryUploadedFile $file) {
                    $manager = new ImageManager(new Driver());

                    $baseDir  = 'categories';
                    $basename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $fileName = Str::slug($basename, '-') . '.webp';
                    $path     = $baseDir . '/' . $fileName;

                    // читаем любой поддерживаемый формат, уменьшаем, конвертируем в webp
                    $img = $manager->read($file->getRealPath())->scaleDown(1600);
                    $binary = $img->toWebp(82);

                    Storage::disk('public')->put($path, (string) $binary);

                    return $path; // относительный путь, сохранится в БД
                })
                ->acceptedFileTypes(['image/*'])
                ->maxSize(8 * 1024)
        ];
    }

    // Глобальное создание (кнопка сверху) — выбираем родителя
    protected function getCreateFormSchema(): array
    {
        return [
            Select::make('parent_id')
                ->label('Родительская категория')
                ->options(fn() => $this->categoryOptions())
                ->default(-1)
                ->searchable()
                ->preload()
                ->required(),
            ...$this->baseCategoryFields(),
        ];
    }

    // Редактирование — можно менять родителя, но запрещаем себя и своих потомков
    protected function getEditFormSchema(): array
    {
        return [
            Select::make('parent_id')
                ->label('Родительская категория')
                ->options(fn() => $this->categoryOptions())
                ->searchable()
                ->preload()
                ->required()
                ->rule(function (Category $record) {
                    return function (string $attribute, $value, \Closure $fail) use ($record) {
                        if ((int)$value === (int)$record->id) {
                            $fail('Нельзя выбрать саму запись как родителя.');
                            return;
                        }
                        // запретим выбирать потомка как родителя
                        if ($this->isDescendant((int)$value, $record->id)) {
                            $fail('Нельзя выбрать дочернюю категорию как родителя.');
                        }
                    };
                }),
            ...$this->baseCategoryFields(),
        ];
    }

    protected function isDescendant(int $candidateId, int $currentId): bool
    {
        if ($candidateId === -1) return false;
        $byParent = Category::query()->select('id', 'parent_id')->get()->groupBy('parent_id');
        $stack = [$currentId];
        while ($stack) {
            $id = array_pop($stack);
            foreach ($byParent[$id] ?? [] as $child) {
                if ((int)$child->id === $candidateId) return true;
                $stack[] = $child->id;
            }
        }
        return false;
    }



    protected function categoryOptions(): array
    {
        // Заберём всё дерево и развернём в плоский список с depth
        $all = Category::query()
            ->orderBy('parent_id')
            ->orderBy('order')
            ->get()
            ->groupBy('parent_id');

        $out = ['-1' => 'Корень'];

        $walk = function (int $parentId, int $depth) use (&$walk, &$out, $all) {
            foreach ($all[$parentId] ?? [] as $cat) {
                $out[$cat->id] = str_repeat('— ', $depth) . $cat->name;
                $walk($cat->id, $depth + 1);
            }
        };

        $walk(-1, 0);

        return $out;
    }



    // CUSTOMIZE ICON OF EACH RECORD, CAN DELETE
    // public function getTreeRecordIcon(?\Illuminate\Database\Eloquent\Model $record = null): ?string
    // {
    //     return null;
    // }
}

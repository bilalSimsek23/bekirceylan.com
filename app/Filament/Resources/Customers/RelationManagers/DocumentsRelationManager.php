<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Models\CustomerDocument;
use App\Support\Options;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Belgeler';

    protected static ?string $modelLabel = 'Belge';

    protected static string|\BackedEnum|null $icon = Heroicon::OutlinedPaperClip;

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('type')->label('Belge Türü')->options(Options::DOCUMENT_TYPES)->required(),
            TextInput::make('title')->label('Başlık'),
            FileUpload::make('file')->label('Dosya')->required()->directory('customers/documents')
                ->visibility('private')->disk('local')
                ->acceptedFileTypes(['image/*', 'application/pdf'])->maxSize(8192)->columnSpanFull(),
            DatePicker::make('expires_at')->label('Geçerlilik Bitiş')->native(false)->displayFormat('d.m.Y'),
            Textarea::make('notes')->label('Not')->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')->label('Tür')->badge()->formatStateUsing(fn ($state) => Options::DOCUMENT_TYPES[$state] ?? $state),
                TextColumn::make('title')->label('Başlık'),
                TextColumn::make('expires_at')->label('Geçerlilik')->date('d.m.Y')
                    ->color(fn (CustomerDocument $r) => $r->expires_at?->isPast() ? 'danger' : null),
                TextColumn::make('created_at')->label('Yüklenme')->date('d.m.Y'),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([
                Action::make('download')->label('İndir')->icon(Heroicon::OutlinedArrowDownTray)
                    ->action(fn (CustomerDocument $r) => Storage::disk('local')->download($r->file)),
                EditAction::make(),
                DeleteAction::make()->after(fn (CustomerDocument $r) => Storage::disk('local')->delete($r->file)),
            ]);
    }
}

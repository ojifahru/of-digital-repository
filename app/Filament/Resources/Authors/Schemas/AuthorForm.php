<?php

namespace App\Filament\Resources\Authors\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AuthorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3) // total grid
            ->components([

                // ================= LEFT (FORM) =================
                Section::make('Informasi Author')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([

                        TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),

                        TextInput::make('identifier')
                            ->label('NIM / NIDN')
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            ->columnSpan(2),

                        RichEditor::make('bio')
                            ->label('Bio Singkat')
                            ->toolbarButtons([
                                ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'link'],
                                ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],
                                ['blockquote', 'codeBlock', 'bulletList', 'orderedList'],
                                ['table'],
                                ['undo', 'redo'],
                            ])
                            ->maxLength(500)
                            ->columnSpan(2),

                    ]),

                // ================= RIGHT (PHOTO) =================
                Section::make('Foto Author')
                    ->columnSpan(1)
                    ->schema([

                        FileUpload::make('image_url')
                            ->label('Foto')
                            ->disk('public')
                            ->directory('authors')
                            ->visibility('public')
                            ->image()
                            ->imageEditor()
                            ->maxSize(2048),

                    ]),
            ]);
    }
}

<?php

namespace App\Filament\Pages;

use App\Settings\PublicSiteSettings;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ManagePublicSiteSettings extends SettingsPage
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?string $navigationLabel = 'Pengaturan Situs';

    protected static ?int $navigationSort = 80;

    protected static ?string $slug = 'settings/public-site';

    protected static string $settings = PublicSiteSettings::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas Situs')
                    ->schema([
                        TextInput::make('site_name')
                            ->label('Nama Situs')
                            ->required()
                            ->maxLength(255),
                        FileUpload::make('logo_path')
                            ->label('Logo Situs')
                            ->disk('public')
                            ->directory('branding')
                            ->visibility('public')
                            ->image()
                            ->imageEditor()
                            ->helperText('Dipakai di header dan footer publik.')
                            ->columnSpanFull(),
                        FileUpload::make('favicon_path')
                            ->label('Favicon')
                            ->disk('public')
                            ->directory('branding')
                            ->visibility('public')
                            ->image()
                            ->imageEditor()
                            ->helperText('Gunakan PNG persegi kecil untuk tab browser.')
                            ->columnSpanFull(),
                        Textarea::make('site_description')
                            ->label('Deskripsi Situs')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                        Textarea::make('footer_tagline')
                            ->label('Tagline Footer')
                            ->required()
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
                Section::make('Hero Beranda')
                    ->schema([
                        TextInput::make('hero_badge')
                            ->label('Badge Hero')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('hero_title')
                            ->label('Judul Hero')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('hero_description')
                            ->label('Deskripsi Hero')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

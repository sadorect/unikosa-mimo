<?php

namespace App\Filament\Concerns;

use App\Models\Chapter;
use App\Models\Due;
use App\Models\Set;
use App\Models\User;
use Filament\Forms;

/**
 * Builds a Repeater field for the polymorphic eligibility_rules relation, shared between
 * ElectionResource (context: voting) and PositionResource/its relation manager
 * (context: candidacy). Each rule type shows only its own config fields, assembled under
 * a `config` key that maps straight onto EligibilityRule::$casts['config'] => 'array'.
 */
trait HasEligibilityRulesRepeater
{
    /**
     * Human-readable labels, keyed by the same `type` string stored on EligibilityRule
     * and resolved by EligibilityService::RULE_TYPES.
     */
    protected static array $eligibilityRuleTypeLabels = [
        'set_range' => 'Graduating set year range',
        'set_list' => 'Specific graduating sets',
        'chapter' => 'Specific chapters',
        'dues_current' => 'Dues up to date',
        'role' => 'Holds role(s)',
        'account_claimed' => 'Account claimed (excludes unclaimed imported records)',
        'manual_list' => 'Specific members (manual list)',
    ];

    public static function eligibilityRulesRepeater(string $context, string $label): Forms\Components\Repeater
    {
        return Forms\Components\Repeater::make('eligibilityRules')
            ->relationship(modifyQueryUsing: fn ($query) => $query->where('context', $context))
            ->label($label)
            ->helperText('Leave empty to allow everyone (all approved members). Every rule added must pass — they combine with AND.')
            ->schema([
                Forms\Components\Hidden::make('context')->default($context),
                Forms\Components\Select::make('type')
                    ->label('Rule type')
                    ->options(static::$eligibilityRuleTypeLabels)
                    ->required()
                    ->live()
                    ->columnSpanFull(),

                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('config.min_year')->label('From year')->numeric(),
                    Forms\Components\TextInput::make('config.max_year')->label('To year')->numeric(),
                ])->visible(fn (Forms\Get $get) => $get('type') === 'set_range'),

                Forms\Components\Select::make('config.set_ids')
                    ->label('Sets')
                    ->multiple()
                    ->options(fn () => Set::orderBy('year', 'desc')->pluck('name', 'id'))
                    ->visible(fn (Forms\Get $get) => $get('type') === 'set_list'),

                Forms\Components\Select::make('config.chapter_ids')
                    ->label('Chapters')
                    ->multiple()
                    ->options(fn () => Chapter::orderBy('name')->pluck('name', 'id'))
                    ->visible(fn (Forms\Get $get) => $get('type') === 'chapter'),

                Forms\Components\Select::make('config.due_ids')
                    ->label('Specific dues')
                    ->helperText('Leave blank to require every due currently applicable to the member.')
                    ->multiple()
                    ->options(fn () => Due::orderBy('name')->pluck('name', 'id'))
                    ->visible(fn (Forms\Get $get) => $get('type') === 'dues_current'),

                Forms\Components\Select::make('config.roles')
                    ->label('Roles')
                    ->multiple()
                    ->options(fn () => \Spatie\Permission\Models\Role::pluck('name', 'name'))
                    ->visible(fn (Forms\Get $get) => $get('type') === 'role'),

                Forms\Components\Select::make('config.user_ids')
                    ->label('Members')
                    ->multiple()
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search) => User::where('name', 'ilike', "%{$search}%")->limit(50)->pluck('name', 'id'))
                    ->getOptionLabelsUsing(fn (array $values) => User::whereIn('id', $values)->pluck('name', 'id'))
                    ->visible(fn (Forms\Get $get) => $get('type') === 'manual_list'),
            ])
            ->itemLabel(fn (array $state) => static::$eligibilityRuleTypeLabels[$state['type'] ?? ''] ?? null)
            ->collapsible()
            ->columnSpanFull();
    }
}

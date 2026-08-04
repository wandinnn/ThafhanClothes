<?php

namespace App\Enums;

enum ProductCondition: string
{
    case New = 'new';
    case SecondLikeNew = 'second_like_new';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::SecondLikeNew => 'Second Like New',
        };
    }

    /**
     * Kelas Tailwind untuk badge di foto etalase.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::New => 'bg-deep !text-cream border border-deep',
            self::SecondLikeNew => 'bg-teal !text-cream border border-teal-dark/30',
        };
    }

    /**
     * @return list<self>
     */
    public static function options(): array
    {
        return self::cases();
    }
}

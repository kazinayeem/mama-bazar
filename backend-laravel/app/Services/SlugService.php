<?php

namespace App\Services;

class SlugService
{
    /** Common Bangla → English loanwords (whole-word lookup, applied first). */
    const BANGLA_DICTIONARY = [
        "টিভি" => "tv",
        "টেলিভিশন" => "television",
        "মোবাইল" => "mobile",
        "ফোন" => "phone",
        "ল্যাপটপ" => "laptop",
        "কম্পিউটার" => "computer",
        "ফ্রিজ" => "fridge",
        "রেফ্রিজারেটর" => "refrigerator",
        "ওয়াশিং" => "washing",
        "মেশিন" => "machine",
        "টেবিল" => "table",
        "চেয়ার" => "chair",
        "ক্যামেরা" => "camera",
        "হেডফোন" => "headphone",
        "স্পিকার" => "speaker",
        "ইয়ারফোন" => "earphone",
        "ফ্যান" => "fan",
        "লাইট" => "light",
        "বাল্ব" => "bulb",
        "চার্জার" => "charger",
        "ব্যাটারি" => "battery",
        "কীবোর্ড" => "keyboard",
        "মাউস" => "mouse",
        "ডিসপ্লে" => "display",
        "স্ক্রিন" => "screen",
        "বাটন" => "button",
        "পাওয়ার" => "power",
        "স্যামসাং" => "samsung",
        "স্মার্ট" => "smart",
        "গ্যাজেট" => "gadget",
        "ওয়াইফাই" => "wifi",
        "ব্লুটুথ" => "bluetooth",
        "মাইক্রোওয়েভ" => "microwave",
        "ওভেন" => "oven",
        "ইলেকট্রিক" => "electric",
        "ট্রান্সফরমার" => "transformer",
        "গ্যাস" => "gas",
        "প্রসেসর" => "processor",
        "মেমোরি" => "memory",
        "সুইচ" => "switch",
        "কেবল" => "cable",
        "টি-শার্ট" => "t-shirt",
        "টি শার্ট" => "t-shirt",
        "শার্ট" => "shirt",
        "প্যান্ট" => "pant",
        "জুতা" => "shoes",
        "স্যান্ডেল" => "sandals",
        "ঘড়ি" => "watch",
        "গ্লাস" => "glass",
        "মগ" => "mug",
        "বালিশ" => "pillow",
        "কম্বল" => "blanket",
        "তালা" => "lock",
        "ছাতা" => "umbrella",
    ];

    /** Single-character Bangla → English transliteration table. */
    const BANGLA_CHARS = [
        // Vowels (independent)
        "অ" => "a", "আ" => "a", "ই" => "i", "ঈ" => "i", "উ" => "u", "ঊ" => "u",
        "ঋ" => "ri", "এ" => "e", "ঐ" => "oi", "ও" => "o", "ঔ" => "ou",
        // Vowel signs (matras)
        "া" => "a", "ি" => "i", "ী" => "i", "ু" => "u", "ূ" => "u", "ৃ" => "ri",
        "ে" => "e", "ৈ" => "oi", "ো" => "o", "ৌ" => "ou", "ৗ" => "o",
        // Consonants
        "ক" => "k", "খ" => "kh", "গ" => "g", "ঘ" => "gh", "ঙ" => "ng",
        "চ" => "ch", "ছ" => "ch", "জ" => "j", "ঝ" => "jh", "ঞ" => "n",
        "ট" => "t", "ঠ" => "th", "ড" => "d", "ঢ" => "dh", "ণ" => "n",
        "ত" => "t", "থ" => "th", "দ" => "d", "ধ" => "dh", "ন" => "n",
        "প" => "p", "ফ" => "f", "ব" => "b", "ভ" => "bh", "ম" => "m",
        "য" => "j", "র" => "r", "ল" => "l", "শ" => "sh", "ষ" => "sh",
        "স" => "s", "হ" => "h", "ৎ" => "t", "ড়" => "r", "ঢ়" => "rh", "য়" => "y",
        "ং" => "ng", "ঃ" => "h", "ঁ" => "n",
        // Digits
        "০" => "0", "১" => "1", "২" => "2", "৩" => "3", "৪" => "4",
        "৫" => "5", "৬" => "6", "৭" => "7", "৮" => "8", "৯" => "9",
    ];

    public static function transliterateBangla(string $input): string
    {
        $words = preg_split('/\s+/', $input, -1, PREG_SPLIT_NO_EMPTY);
        $mappedWords = array_map(function ($word) {
            return self::BANGLA_DICTIONARY[$word] ?? $word;
        }, $words);
        $out = implode(' ', $mappedWords);

        // Character by character transliteration using UTF-8 split
        $chars = mb_str_split($out);
        $mappedChars = array_map(function ($ch) {
            return self::BANGLA_CHARS[$ch] ?? $ch;
        }, $chars);

        return implode('', $mappedChars);
    }

    public static function toAsciiSlug(string $value): string
    {
        $transliterated = self::transliterateBangla($value);
        $lower = strtolower($transliterated);
        $slug = preg_replace('/[^a-z0-9]+/i', '-', $lower);
        return trim($slug, '-');
    }

    public static function hasUnsupportedSlugChars(string $value): bool
    {
        $hasBangla = preg_match('/[\x{0980}-\x{09FF}]/u', $value);
        $check = $hasBangla ? self::transliterateBangla($value) : $value;
        return (bool) preg_match('/[^\x00-\x7F]/', $check);
    }
}

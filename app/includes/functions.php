<?php
function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
function csrf()
{
    if (empty($_SESSION['token'])) $_SESSION['token'] = bin2hex(random_bytes(24));
    return $_SESSION['token'];
}
function check_post()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals(csrf(), $_POST['token'] ?? '')) {
        http_response_code(403);
        exit('Requête refusée.');
    }
}

/**
 * Couleurs autorisées pour une catégorie : les pastels de la charte.
 * Doit rester identique à la contrainte CHECK de categories.color (migrations/init.sql).
 */
function category_colors(): array
{
    return [
        'peach' => 'Pêche',
        'mint'  => 'Menthe',
        'lemon' => 'Citron',
        'lilac' => 'Lilas',
        'pink'  => 'Rose',
        'sky'   => 'Ciel',
    ];
}

/**
 * Source unique des catégories, lue en base (avant : liste codée en dur).
 * [slug => ['id' => …, 'label' => nom affiché, 'tone' => couleur, 'picture' => image par défaut, 'dishes' => nombre de plats]]
 * Triées par date de création : les catégories d'origine d'abord, les nouvelles ensuite.
 * Une seule requête par page, grâce au cache statique.
 */
function categories(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    global $db;
    $rows = $db->query(
        'SELECT c.id, c.slug, c.name, c.color, c.picture, COUNT(dc.dish_id) AS dishes
         FROM categories c
         LEFT JOIN dish_categories dc ON dc.category_id = c.id
         GROUP BY c.id
         ORDER BY c.id'
    )->fetchAll();

    $cache = [];
    foreach ($rows as $row) {
        $cache[$row['slug']] = [
            'id'     => (int) $row['id'],
            'label'  => $row['name'],
            'tone'    => $row['color'],
            'picture' => $row['picture'],
            'dishes'  => (int) $row['dishes'],
        ];
    }
    return $cache;
}

/** Catégories jouables en duel : il faut au moins 2 plats pour faire un duel. */
function duel_categories(): array
{
    return array_filter(categories(), static fn(array $c): bool => $c['dishes'] >= 2);
}

/** « Plats indiens » → « plats-indiens ». Sans dépendance à une extension PHP. */
function slugify(string $text): string
{
    $text = mb_strtolower(trim($text), 'UTF-8');
    $text = strtr($text, [
        'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a', 'å' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'î' => 'i', 'ï' => 'i', 'í' => 'i', 'ì' => 'i',
        'ô' => 'o', 'ö' => 'o', 'ó' => 'o', 'ò' => 'o', 'õ' => 'o',
        'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u',
        'ç' => 'c', 'ñ' => 'n', 'ÿ' => 'y', 'œ' => 'oe', 'æ' => 'ae', '&' => '-et-',
    ]);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

/**
 * Clé de comparaison anti-doublon : slug sans le pluriel de chaque mot.
 * « Pizzas », « PIZZAS » et « pizza » → « pizza » ; « Pâtes fraîches » → « pate-fraiche ».
 */
function category_key(string $text): string
{
    $words = explode('-', slugify($text));
    $words = array_map(
        static fn(string $w): string => strlen($w) > 3 ? preg_replace('/[sx]$/', '', $w) : $w,
        $words
    );
    return implode('-', $words);
}

/** Catégorie existante équivalente au nom saisi (même slug, même nom ou simple pluriel), sinon null. */
function find_similar_category(string $name): ?array
{
    $key = category_key($name);
    foreach (categories() as $slug => $category) {
        if ($key === category_key($slug) || $key === category_key($category['label'])) {
            return $category;
        }
    }
    return null;
}

function category_label(string $key): string
{
    return categories()[$key]['label'] ?? ucfirst($key);
}
function category_tone(string $key): string
{
    return categories()[$key]['tone'] ?? 'white';
}

/** Note moyenne formatée, « — » quand le plat n'a pas encore d'avis publié. */
function rating_label($average): string
{
    return $average === null ? '—' : number_format((float) $average, 1, ',', '');
}

/**
 * Image par défaut d'une catégorie : la photo obligatoire choisie à sa création.
 * Image neutre si la catégorie est inconnue (avant : toujours un burger).
 * Les vues l'utilisent en dernier recours : $dish['picture'] ?: image_for($categorie).
 */
function image_for($category)
{
    return categories()[$category]['picture'] ?? '/assets/images/placeholder.svg';
}
function euro($n)
{
    return number_format((float)$n, 2, ',', ' ') . ' €';
}

/** « 2026-10-26 » → « 26 oct. 2026 », sans dépendre de l'extension intl (absente de l'image Docker). */
function date_fr(?string $date): string
{
    if (!$date) {
        return '';
    }
    $months = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
    $time = strtotime($date);
    return date('j', $time) . ' ' . $months[(int) date('n', $time) - 1] . ' ' . date('Y', $time);
}

/** « 1 vote », « 3 votes » ; $plural sert aux pluriels irréguliers. */
function plural(int $n, string $singular, ?string $plural = null): string
{
    return $n . ' ' . ($n > 1 ? ($plural ?? $singular . 's') : $singular);
}

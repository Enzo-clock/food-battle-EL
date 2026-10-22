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
 * Source unique des catégories (avant : liste codée en dur dans 4 fichiers).
 * La clé correspond à la valeur stockée dans dishes.category.
 */
function categories(): array
{
    return [
        'burger' => ['label' => 'Burgers', 'tone' => 'peach'],
        'tacos'  => ['label' => 'Tacos',   'tone' => 'mint'],
        'pizza'  => ['label' => 'Pizzas',  'tone' => 'lemon'],
    ];
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

function image_for($category)
{
    $photos = ['burger' => '1639557', 'tacos' => '4958792', 'pizza' => '315755'];
    $id = $photos[$category] ?? '1639557';
    return 'https://images.pexels.com/photos/' . $id . '/pexels-photo-' . $id . '.jpeg?auto=compress&cs=tinysrgb&w=800';
}
function euro($n)
{
    return number_format((float)$n, 2, ',', ' ') . ' €';
}

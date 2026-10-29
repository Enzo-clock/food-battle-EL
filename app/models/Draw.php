<?php
// filepath: app/models/Draw.php

/**
 * Tirage au sort d'un plat : « Mon plat du jour… ? ».
 *
 * Seuls les plats visibles peuvent sortir : la vue active_dishes exclut les plats archivés
 * et les propositions pas encore validées par un admin (migrations 002 et 004).
 *
 * Le hasard est décidé ici, côté serveur, et jamais dans le navigateur :
 *   - random_int() s'appuie sur le générateur aléatoire cryptographique du système :
 *     chaque plat a exactement 1 chance sur N (distribution uniforme) ;
 *   - le navigateur reçoit le gagnant déjà tiré : l'animation n'est qu'une mise en scène,
 *     elle ne peut ni changer ni influencer le résultat.
 */
class Draw
{
    /** Nombre maximal de plats affichés pendant le défilement (images à charger sur mobile). */
    public const REEL_SIZE = 10;

    public function __construct(private PDO $db)
    {
    }

    /**
     * Identifiants des plats qui peuvent sortir, toutes catégories ou une seule.
     * $category est un slug déjà validé par l'appelant (catégorie existante et approuvée).
     *
     * @return int[]
     */
    public function eligibleIds(?string $category = null): array
    {
        $sql = 'SELECT d.id FROM active_dishes d';
        $params = [];
        if ($category !== null) {
            $sql .= " WHERE EXISTS (
                SELECT 1 FROM dish_categories dc
                JOIN categories c ON c.id = dc.category_id
                WHERE dc.dish_id = d.id AND c.slug = ? AND c.status = 'approved'
            )";
            $params[] = $category;
        }
        $stmt = $this->db->prepare($sql . ' ORDER BY d.id');
        $stmt->execute($params);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * Un tirage complet : le plat gagnant, les plats du défilement et le nombre de plats en lice.
     * Retourne null quand aucun plat ne peut sortir.
     *
     * @return array{winner: array, reel: array[], total: int}|null
     */
    public function draw(?string $category = null, int $attempt = 1): ?array
    {
        $ids = $this->eligibleIds($category);
        $total = count($ids);
        if ($total === 0) {
            return null;
        }

        // Le tirage lui-même : un index entre 0 et N-1, chaque plat a la même chance.
        $winnerId = $ids[random_int(0, $total - 1)];

        // Défilement : quelques autres plats en lice, pris au hasard, sans le gagnant.
        // Ils ne servent qu'à l'animation et n'ont aucun effet sur le résultat.
        $others = array_values(array_diff($ids, [$winnerId]));
        shuffle($others);
        $reelIds = array_slice($others, 0, self::REEL_SIZE);

        $dishes = $this->dishes(array_merge([$winnerId], $reelIds));
        if (!isset($dishes[$winnerId])) {
            // Plat archivé ou retiré entre les deux requêtes : cas rarissime, on tire à nouveau
            // (3 essais au plus, pour ne jamais boucler sans fin).
            if ($attempt >= 3) {
                throw new RuntimeException('Tirage impossible : les plats ont changé pendant le tirage.');
            }
            return $this->draw($category, $attempt + 1);
        }

        return [
            'winner' => $dishes[$winnerId],
            'reel'   => array_values(array_intersect_key($dishes, array_flip($reelIds))),
            'total'  => $total,
        ];
    }

    /**
     * Données d'affichage des plats demandés, prêtes pour la carte : [id => plat].
     * Catégorie principale = la première par ordre alphabétique, comme sur l'accueil.
     */
    private function dishes(array $ids): array
    {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare(
            "SELECT d.id, d.name, d.description, d.picture, d.price,
                    e.name AS restaurant, e.city,
                    cat.slug AS category
             FROM active_dishes d
             LEFT JOIN establishments e ON e.id = d.establishment_id
             LEFT JOIN LATERAL (
                 SELECT MIN(c.slug) AS slug
                 FROM dish_categories dc
                 JOIN categories c ON c.id = dc.category_id
                 WHERE dc.dish_id = d.id AND c.status = 'approved'
             ) cat ON TRUE
             WHERE d.id IN ($placeholders)"
        );
        $stmt->execute($ids);

        $dishes = [];
        foreach ($stmt->fetchAll() as $row) {
            $dishes[(int) $row['id']] = self::present($row);
        }
        return $dishes;
    }

    /**
     * Un plat tel que la page et l'API l'affichent : textes bruts (échappés à l'affichage),
     * prix déjà formaté, image de repli de la catégorie si le plat n'a pas de photo.
     */
    private static function present(array $row): array
    {
        $category = (string) ($row['category'] ?? '');
        return [
            'id'          => (int) $row['id'],
            'name'        => $row['name'],
            'description' => (string) ($row['description'] ?? ''),
            'place'       => trim(($row['restaurant'] ?: 'Plat maison') . (!empty($row['city']) ? ' · ' . $row['city'] : '')),
            'price'       => $row['price'] !== null ? euro($row['price']) : 'Prix non renseigné',
            'picture'     => $row['picture'] ?: image_for($category),
            'category'    => $category !== '' ? category_label($category) : 'Sans catégorie',
            'tone'        => $category !== '' ? category_tone($category) : 'white',
        ];
    }
}

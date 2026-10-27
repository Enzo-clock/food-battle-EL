<?php
// filepath: app/views/partials/restaurant-fields.php
// Champs du restaurant, communs à la création et à la modification. Variable : $old
?>
<div class="create-form__grid create-form__grid--even">
    <fieldset class="create-form__group">
        <legend>L’établissement</legend>

        <div class="field">
            <label for="name">Nom <span class="field__req" aria-hidden="true">*</span></label>
            <input id="name" name="name" maxlength="150" required autocomplete="organization"
                placeholder="Ex. Le Comptoir Smash" value="<?= h($old['name']) ?>" data-counter>
        </div>

        <div class="field">
            <label for="description">Description <span class="field__opt">(facultatif)</span></label>
            <textarea id="description" name="description" rows="4" maxlength="1000"
                placeholder="Spécialités, ambiance, ce qui le rend unique…" data-counter><?= h($old['description']) ?></textarea>
        </div>
    </fieldset>

    <fieldset class="create-form__group">
        <legend>Où le trouver</legend>

        <div class="field">
            <label for="address">Adresse <span class="field__req" aria-hidden="true">*</span></label>
            <input id="address" name="address" required autocomplete="street-address"
                placeholder="Ex. 12 rue des Ateliers" value="<?= h($old['address']) ?>">
        </div>

        <div class="field">
            <label for="city">Ville <span class="field__req" aria-hidden="true">*</span></label>
            <input id="city" name="city" maxlength="80" required autocomplete="address-level2"
                placeholder="Ex. Lille" value="<?= h($old['city']) ?>">
        </div>
    </fieldset>
</div>

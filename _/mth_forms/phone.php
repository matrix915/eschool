<?php

function printPhoneFields($fieldName, $required = false, mth_phone $phone = NULL)
{
    $required = $required ? 'required' : '';
    $id = str_replace(array('[', ']'), array('-', ''), $fieldName);
    ?>
    <div class="mth_phone form-row" id="mth_phone-<?= $id ?>">
        <?php if ($phone): ?>
            <input type="hidden" name="<?= $fieldName ?>[id]" value="<?= $phone->getID() ?>">
        <?php endif; ?>
        <div class="col">
            <select name="<?= $fieldName ?>[name]" id="<?= $id ?>-name" class="mth_phone_name  form-control" <?= $required ?>>
                <option></option>
                <?php foreach (mth_phone::getAvailableNames() as $name): ?>
                    <option <?= $phone && $phone->getName() === $name ? 'selected' : '' ?>><?= $name ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col">
        <input type="text" name="<?= $fieldName ?>[number]" id="<?= $id ?>-number"
               value="<?= $phone ? $phone->getNumber() : '' ?>"
               class="mth_phone_number form-control" <?= $required ?>>
        </div>
        <div class="col">
        <input type="text"placeholder="EXT." name="<?= $fieldName ?>[ext]" id="<?= $id ?>-ext"
                   value="<?= $phone ? $phone->getExt() : '' ?>"
                   class="mth_phone_ext  form-control">
        <label for="<?= $id ?>-number" class="error"></label>
        </div>
    </div>
    <?php

}
<?php
/**
 * Menu manager: categories and items (add, edit, reorder, hide, sold out, delete).
 */
require __DIR__ . '/_init.php';

$pdo = db();

/** Move a row up/down by swapping it with its neighbour, then renumber. */
function move_row(string $table, int $id, string $dir, ?int $categoryId = null): void
{
    $pdo = db();
    $sql = "SELECT id FROM $table" . ($categoryId !== null ? ' WHERE category_id = ' . (int) $categoryId : '') . ' ORDER BY sort, id';
    $ids = array_map('intval', $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN));
    $pos = array_search($id, $ids, true);
    $to  = $dir === 'up' ? $pos - 1 : $pos + 1;
    if ($pos === false || $to < 0 || $to >= count($ids)) {
        return;
    }
    [$ids[$pos], $ids[$to]] = [$ids[$to], $ids[$pos]];
    $upd = $pdo->prepare("UPDATE $table SET sort = ? WHERE id = ?");
    foreach ($ids as $i => $rowId) {
        $upd->execute([$i + 1, $rowId]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = post('action');
    $id     = (int) post('id');
    $catId  = (int) post('category_id');
    $anchor = '';

    switch ($action) {
        case 'cat_add':
            if (post('title_fa') === '' || post('title_en') === '') {
                flash('error', 'عنوان فارسی و انگلیسی دسته را وارد کنید.');
                break;
            }
            $sort = (int) $pdo->query('SELECT COALESCE(MAX(sort), 0) + 1 FROM categories')->fetchColumn();
            $pdo->prepare('INSERT INTO categories (icon, title_fa, title_en, note_fa, note_en, sort) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([in_array(post('icon'), CATEGORY_ICONS, true) ? post('icon') : 'cup', post('title_fa'), post('title_en'), post('note_fa'), post('note_en'), $sort]);
            $anchor = '#cat-' . $pdo->lastInsertId();
            flash('success', 'دسته جدید اضافه شد.');
            break;

        case 'cat_save':
            $pdo->prepare('UPDATE categories SET icon = ?, title_fa = ?, title_en = ?, note_fa = ?, note_en = ?, visible = ? WHERE id = ?')
                ->execute([in_array(post('icon'), CATEGORY_ICONS, true) ? post('icon') : 'cup', post('title_fa'), post('title_en'), post('note_fa'), post('note_en'), isset($_POST['visible']) ? 1 : 0, $id]);
            $anchor = '#cat-' . $id;
            flash('success', 'دسته ذخیره شد.');
            break;

        case 'cat_delete':
            $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
            flash('success', 'دسته و آیتم‌هایش حذف شدند.');
            break;

        case 'cat_up':
        case 'cat_down':
            move_row('categories', $id, $action === 'cat_up' ? 'up' : 'down');
            $anchor = '#cat-' . $id;
            break;

        case 'item_add':
            if (post('name_fa') === '' || post('name_en') === '') {
                flash('error', 'نام فارسی و انگلیسی آیتم را وارد کنید.');
                $anchor = '#cat-' . $catId;
                break;
            }
            $sort = $pdo->prepare('SELECT COALESCE(MAX(sort), 0) + 1 FROM items WHERE category_id = ?');
            $sort->execute([$catId]);
            $pdo->prepare('INSERT INTO items (category_id, name_fa, name_en, price, is_addon, sort) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([$catId, post('name_fa'), post('name_en'), parse_price(post('price')), isset($_POST['is_addon']) ? 1 : 0, (int) $sort->fetchColumn()]);
            $anchor = '#cat-' . $catId;
            flash('success', 'آیتم اضافه شد.');
            break;

        case 'item_save':
            $pdo->prepare('UPDATE items SET name_fa = ?, name_en = ?, price = ?, is_addon = ?, available = ? WHERE id = ?')
                ->execute([post('name_fa'), post('name_en'), parse_price(post('price')), isset($_POST['is_addon']) ? 1 : 0, isset($_POST['available']) ? 1 : 0, $id]);
            $anchor = '#item-' . $id;
            flash('success', 'آیتم ذخیره شد.');
            break;

        case 'item_delete':
            $pdo->prepare('DELETE FROM items WHERE id = ?')->execute([$id]);
            $anchor = '#cat-' . $catId;
            flash('success', 'آیتم حذف شد.');
            break;

        case 'item_up':
        case 'item_down':
            move_row('items', $id, $action === 'item_up' ? 'up' : 'down', $catId);
            $anchor = '#item-' . $id;
            break;
    }
    redirect('menu.php' . $anchor);
}

$menu = menu_load(true);
$iconLabels = ['sandwich' => 'ساندویچ', 'leaf' => 'دمنوش', 'cup' => 'فنجان', 'ice' => 'نوشیدنی سرد', 'mug' => 'ماگ', 'drip' => 'قهوه دمی', 'smoothie' => 'اسموتی', 'cake' => 'کیک', 'glass' => 'ماکتیل', 'shake' => 'شیک'];

function icon_select(string $current, array $labels): string
{
    $html = '<select name="icon">';
    foreach (CATEGORY_ICONS as $ic) {
        $html .= '<option value="' . $ic . '"' . ($ic === $current ? ' selected' : '') . '>' . e($labels[$ic] ?? $ic) . '</option>';
    }
    return $html . '</select>';
}

admin_header('مدیریت منو', 'menu');
?>

<p class="muted">قیمت‌ها به <strong>هزار تومان</strong> هستند (مثلا ۲۸۰). اگر قیمت را خالی بگذارید «قیمت روز» نمایش داده می‌شود. اعداد فارسی هم قابل قبول است.</p>

<nav class="jump">
    <?php foreach ($menu as $cat): ?>
        <a href="#cat-<?= (int) $cat['id'] ?>" class="<?= $cat['visible'] ? '' : 'is-hidden' ?>"><?= e($cat['title_fa']) ?> <small><?= num(count($cat['items']), 'fa') ?></small></a>
    <?php endforeach; ?>
    <a href="#new-cat" class="jump__add">+ دسته جدید</a>
</nav>

<?php foreach ($menu as $ci => $cat): $cid = (int) $cat['id']; ?>
    <section class="card cat <?= $cat['visible'] ? '' : 'cat--hidden' ?>" id="cat-<?= $cid ?>">
        <div class="cat__head">
            <span class="cat__icon"><?= icon($cat['icon']) ?></span>
            <div class="cat__title">
                <h2><?= e($cat['title_fa']) ?> <?php if (!$cat['visible']): ?><span class="badge badge--muted">مخفی</span><?php endif; ?></h2>
                <small dir="ltr"><?= e($cat['title_en']) ?></small>
            </div>
            <form method="post" class="cat__order">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $cid ?>">
                <button class="icon-btn" name="action" value="cat_up" title="بالا" <?= $ci === 0 ? 'disabled' : '' ?>>▲</button>
                <button class="icon-btn" name="action" value="cat_down" title="پایین" <?= $ci === count($menu) - 1 ? 'disabled' : '' ?>>▼</button>
            </form>
        </div>

        <details class="cat__edit">
            <summary>ویرایش دسته</summary>
            <form method="post" class="form form--grid">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $cid ?>">
                <label class="field"><span>عنوان فارسی</span><input name="title_fa" value="<?= e($cat['title_fa']) ?>" required></label>
                <label class="field"><span>عنوان انگلیسی</span><input name="title_en" value="<?= e($cat['title_en']) ?>" dir="ltr" required></label>
                <label class="field"><span>توضیح فارسی (اختیاری)</span><input name="note_fa" value="<?= e($cat['note_fa']) ?>" placeholder="مثلا: قیمت روز"></label>
                <label class="field"><span>توضیح انگلیسی (اختیاری)</span><input name="note_en" value="<?= e($cat['note_en']) ?>" dir="ltr"></label>
                <label class="field"><span>آیکون</span><?= icon_select($cat['icon'], $iconLabels) ?></label>
                <label class="check"><input type="checkbox" name="visible" <?= $cat['visible'] ? 'checked' : '' ?>> نمایش در منو</label>
                <div class="form__actions">
                    <button class="btn btn--primary" name="action" value="cat_save">ذخیره دسته</button>
                    <button class="btn btn--danger" name="action" value="cat_delete" data-confirm="دسته «<?= e($cat['title_fa']) ?>» و همه آیتم‌هایش حذف شود؟">حذف دسته</button>
                </div>
            </form>
        </details>

        <div class="items-head">
            <span>نام فارسی</span><span>نام انگلیسی</span><span>قیمت</span><span>وضعیت</span><span></span>
        </div>
        <?php foreach ($cat['items'] as $ii => $item): $iid = (int) $item['id']; ?>
            <form method="post" class="item-row <?= $item['available'] ? '' : 'item-row--off' ?>" id="item-<?= $iid ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $iid ?>">
                <input type="hidden" name="category_id" value="<?= $cid ?>">
                <input name="name_fa" value="<?= e($item['name_fa']) ?>" aria-label="نام فارسی" required>
                <input name="name_en" value="<?= e($item['name_en']) ?>" aria-label="نام انگلیسی" dir="ltr" required>
                <input name="price" value="<?= $item['price'] === null ? '' : (int) $item['price'] ?>" aria-label="قیمت" inputmode="numeric" placeholder="قیمت روز" class="price-input" dir="ltr">
                <div class="item-row__flags">
                    <label class="check" title="اگر تیک نداشته باشد «ناموجود» نمایش داده می‌شود"><input type="checkbox" name="available" <?= $item['available'] ? 'checked' : '' ?>> موجود</label>
                    <label class="check"><input type="checkbox" name="is_addon" <?= $item['is_addon'] ? 'checked' : '' ?>> افزودنی</label>
                </div>
                <div class="item-row__actions">
                    <button class="icon-btn icon-btn--save" name="action" value="item_save" title="ذخیره">✓</button>
                    <button class="icon-btn" name="action" value="item_up" title="بالا" <?= $ii === 0 ? 'disabled' : '' ?>>▲</button>
                    <button class="icon-btn" name="action" value="item_down" title="پایین" <?= $ii === count($cat['items']) - 1 ? 'disabled' : '' ?>>▼</button>
                    <button class="icon-btn icon-btn--danger" name="action" value="item_delete" title="حذف" data-confirm="«<?= e($item['name_fa']) ?>» حذف شود؟" formnovalidate>✕</button>
                </div>
            </form>
        <?php endforeach; ?>

        <form method="post" class="item-row item-row--new">
            <?= csrf_field() ?>
            <input type="hidden" name="category_id" value="<?= $cid ?>">
            <input name="name_fa" placeholder="نام فارسی آیتم جدید" required>
            <input name="name_en" placeholder="English name" dir="ltr" required>
            <input name="price" placeholder="قیمت" inputmode="numeric" class="price-input" dir="ltr">
            <div class="item-row__flags">
                <label class="check"><input type="checkbox" name="is_addon"> افزودنی</label>
            </div>
            <div class="item-row__actions">
                <button class="btn btn--primary btn--sm" name="action" value="item_add">+ افزودن</button>
            </div>
        </form>
    </section>
<?php endforeach; ?>

<section class="card" id="new-cat">
    <h2>افزودن دسته جدید</h2>
    <form method="post" class="form form--grid">
        <?= csrf_field() ?>
        <label class="field"><span>عنوان فارسی</span><input name="title_fa" required></label>
        <label class="field"><span>عنوان انگلیسی</span><input name="title_en" dir="ltr" required></label>
        <label class="field"><span>توضیح فارسی (اختیاری)</span><input name="note_fa"></label>
        <label class="field"><span>توضیح انگلیسی (اختیاری)</span><input name="note_en" dir="ltr"></label>
        <label class="field"><span>آیکون</span><?= icon_select('cup', $iconLabels) ?></label>
        <div class="form__actions">
            <button class="btn btn--primary" name="action" value="cat_add">افزودن دسته</button>
        </div>
    </form>
</section>

<?php admin_footer(); ?>

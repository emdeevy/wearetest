<?php
declare(strict_types=1);

class ItemsHandler
{
    private array $itemCriteria = [
        "game_name" => "",
        "id" => "",
        "is_mobile" => 0,
        "is_live" => 0,
        "width" => 1280,
        "height" => 720
    ];

    private array $existingItem = [
        "game_name" => "Yo-Ho Gold!",
        "id" => "yo_ho_gold",
        "is_mobile" => 0,
        "is_live" => 0,
        "width" => 1280,
        "height" => 720
    ];

    public function getItemCriteria(): array
    {
        return $this->itemCriteria;
    }

    public function itemExists(array $item): bool
    {
        foreach ($item as $key => $value) {
            if ($this->existingItem[$key] != $value) {
                return false;
            }
        }

        return true;
    }
}

function saveItems(string $items): void
{
    global $savedItemCount, $savedNames;

    $items = json_decode($items, true);

    $handler = new ItemsHandler();

    foreach ($items['items'] as $_item) {
        // get relevant dates, if release date in json isn't right, we try next (error handling isn't relevant for this task)
        try {
            $releaseDate = new DateTime($_item['release_date']);
            $currentDate = new DateTime('now');
        }
        catch (Exception) {
            continue;
        }

        if(!$_item['active'] || $releaseDate > $currentDate) {
            continue; // avoid unnecessary computation
        }

        // desktop item
        $desktopItem = array_merge($handler->getItemCriteria(), [
            "game_name" => $_item['details']['i18n']['en'],
            "id" => $_item['game_name'],
            "is_mobile" => 0,
            "is_live" => $_item['type'] !== 'slots'
        ]);

        // mobile item
        $mobileItem = array_merge($desktopItem, ["is_mobile" => 1]);

        foreach([$desktopItem, $mobileItem] as $item) {
            !$handler->itemExists($item) && ++$savedItemCount && $savedNames[] = ($item['is_mobile'] ? 'mobile:' : 'desktop:') . $item['id'];
        }
    }
}

$savedItemCount = 0;
$savedNames = [];

$items = file_get_contents('./wearegen/priv/items.json');

saveItems($items);

printf("Saved %d items\n%s.\n", $savedItemCount, (empty($savedNames) ?: "[" . implode(", ", $savedNames) . "]"));
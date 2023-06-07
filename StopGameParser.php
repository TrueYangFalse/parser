<?php




class StopGameParser
{
    private Http $http;
    private string $url;

    public function __construct(Http $http, string $url)
    {
        // echo "Я конструктор\n";
        $this->url = $url;
        $this->http = $http;
    }

    public function parseCards(array $cardsInfos): array
    {
        $games = [];
        foreach ($cardsInfos as $cardInfo) {
            $html = $this->http->get($this->url . $cardInfo['link']);
            $dom = str_get_html($html);

            $titles = $dom->find('._info-grid__title_sh7r2_199');
            $infos = $dom->find('._info-grid__value_sh7r2_200');
            $view = $dom->find('._subscribers-info_sh7r2_348', 0);
            $name = $dom->find('._title_qrsvr_270', 0);
            $graphics = $dom->find('._requirements__value_qrsvr_1', 3);

            $game = [
                'views' => $view->plaintext,
                'name' => $name->plaintext,
                'img' => $cardInfo['img'],
                'link' => $this->url . $cardInfo['link'],
            ];


            $other = [];
            foreach ($titles as $key => $title) {
                $titleKey= $title->plaintext;
                $info = $infos[$key];
                $infoKey = trim($info->plaintext);
                switch (trim($titleKey)) {
                    case 'Разработчик':
                        $game['developer'] = $infoKey;
                        break;
                    case 'Сайт игры':
                        $game['site'] = $infoKey;
                        break;
                    case 'Издатель':
                        $game['publisher'] = $infoKey;
                        break;
                    case 'Платформы':
                        $game['platforms'] = $infoKey;
                        break;
                    case 'Жанры':
                        $game['genres'] = $infoKey;
                        break;
                    case 'Дата выхода':
                        $game['release_date'] = $infoKey ;
                        break;
                }

            }



            if ($graphics != '') {
                $game['minimal_graphics'] = $graphics->plaintext;
            } else {
                $game['minimal_graphics'] ='Нету информации';
            }
//            $this->extractTable($game);
            $result = array_merge($other, $game);


            print_r($game);
            $games[] = $result;

            $connect = mysqli_connect('localhost','root','','Parse');
            if(!$connect){
                die('Ошибка подключения к БД');
            }
            foreach ($game as $key => $value) {
                $game[$key] = mysqli_real_escape_string($connect, $value);
            }

            mysqli_query($connect, "INSERT INTO `Games` (`id`, `img`, `link`, `name`, `minimal_graphics`, `views`, `game_site`, `developer`, `publisher`, `platforms`, `genres`, `release_date`)
    VALUES (NULL, '{$game['img']}', '{$game['link']}', '{$game['name']}', '{$game['minimal_graphics']}', '{$game['views']}', '{$game['game_site']}', '{$game['developer']}', '{$game['publisher']}', '{$game['platforms']}', '{$game['genres']}', '{$game['release_date']}')");



        }


        return $games;


    }



    public function getPageCards($pageNumber)
    {
        $page = $this->http->get(
            $this->url . '/games/catalog',
            ['p' => $pageNumber]
        ); // Получаем html код страницы
        $html = str_get_html($page); //Преобразуем текст в обьект с удобными методами вроде find
        $found = $html->find('._card_13hsk_1');
        $info = [];

        foreach ($found as $element) {
            $img = $element->find('._image_13hsk_7', 0);
            $info[] = [
                'img' => $img->src,
                'link' => $element->href,
            ];

        }
//        print_r($info);
        return $info;
    }

    public function parse(int $limit)
    {
        // В $games складываем массив со всей нужной информацией по всем играм
        $games = [];
        for ($page = 1; $page <= $limit; $page++) {
            $cards = $this->getPageCards($page);
            $games1 = $this->parseCards($cards);
            $games[] = array_merge($cards, $games1);



        }
        return $games;
        //getPageCards это парсин страницы целиком parseCards это парсинг внутри картачки на игру

    }

}

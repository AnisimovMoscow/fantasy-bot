<?php

namespace app\controllers;

use app\components\Message;
use app\models\User;
use DateTime;
use DateTimeZone;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use Yii;
use yii\helpers\Url;
use yii\web\Controller;

/**
 * Обработка запросов
 */
class SiteController extends Controller
{
    const RE_PROFILE = '/sports\.ru\/profile\/(\d+)/';
    const SITE = 'www.sports.ru';

    public function actionIndex()
    {
        return '';
    }

    /**
     * Прием запросов от сервера Телеграм
     */
    public function actionHook()
    {
        $update = Yii::$app->request->post();
        Yii::info(print_r($update, true), 'hook');

        if (isset($update['message']['text']) && $update['message']['chat']['type'] == 'private') {
            $params = explode(' ', $update['message']['text']);
            $command = array_shift($params);
            $method = 'command' . ucfirst(ltrim($command, '/'));
            if (method_exists($this, $method) && is_callable([$this, $method])) {
                $this->$method($params, $update['message']['chat']);
            } elseif (preg_match(self::RE_PROFILE, $command)) {
                $this->commandProfile([$command], $update['message']['chat']);
            } else {
                $this->unknownCommand($update['message']['chat']);
            }
        }
    }

    /**
     * Начало работы, регистрация пользователя
     */
    public function commandStart($params, $chat)
    {
        $user = User::findOne(['chat_id' => $chat['id']]);
        if ($user === null) {
            $user = new User([
                'chat_id' => $chat['id'],
                'first_name' => isset($chat['first_name']) ? $chat['first_name'] : '',
                'last_name' => isset($chat['last_name']) ? $chat['last_name'] : '',
                'username' => isset($chat['username']) ? $chat['username'] : '',
                'sports_id' => '',
                'notification' => true,
            ]);
            $user->save();
            $message = 'Привет! Отправь мне ссылку на свой профиль и я буду напоминать тебе о важных событиях';
        } elseif (empty($user->sports_id)) {
            $message = 'Ты ещё не отправил мне ссылку на свой профиль';
        } elseif (!$user->notification) {
            $user->notification = true;
            $user->save();
            $message = 'Ты подписался на уведомления. Если захочешь отписаться, набери /stop';
        } else {
            $message = 'Привет! Если тебе нужна помощь, набери /help';
        }

        Message::send($chat['id'], $message, $user);
    }

    /**
     * Установка ссылки на профиль
     */
    public function commandProfile($params, $chat)
    {
        $user = User::findOne(['chat_id' => $chat['id']]);
        if (count($params) == 1) {
            if (preg_match(self::RE_PROFILE, $params[0], $matches)) {
                if ($user === null) {
                    $message = 'Кажется мы ещё не здоровались. Отправь мне /start';
                } else {
                    $checkUser = User::findOne(['sports_id' => $matches[1]]);
                    if ($checkUser !== null && $checkUser->id !== $user->id) {
                        $message = 'Пользователь с такой ссылкой уже зарегистрирован.';
                    } else {
                        $user->sports_id = $matches[1];
                        $user->save();
                        $user->updateTeams();
                        $message = 'Всё отлично. Молодец! Я запомнил ссылку на твой профиль. ';
                        $message .= 'Чтоб посмотреть список команд, отправь мне /teams';
                    }
                }
            } else {
                $message = 'Ты мне неправильно отправил ссылку. Просто зайди на сайт ' . self::SITE . ' и ';
                $message .= 'скопируй ссылку на свою страницу. ';
                $message .= 'Там должны быть ссылка вида https://' . self::SITE . '/profile/12345/';
            }
        } else {
            $message = 'Нужно просто отправить ссылку на свой профиль';
        }

        Message::send($chat['id'], $message, $user);
    }

    /**
     * Список дедлайнов
     */
    public function commandDeadlines($params, $chat)
    {
        $user = User::findOne(['chat_id' => $chat['id']]);
        if ($user === null) {
            $message = 'Кажется мы ещё не здоровались. Отправь мне /start';
        } else {
            if (count($user->teams) > 0) {
                $message = 'Дедлайны:';
                $deadlines = [];
                foreach ($user->teams as $team) {
                    $deadlines[$team->tournament->name] = $team->tournament->deadline;
                }
                asort($deadlines);
                foreach ($deadlines as $name => $deadline) {
                    if ($deadline === null) {
                        continue;
                    }
                    $date = new DateTime($deadline, new DateTimeZone(Yii::$app->timeZone));
                    $date->setTimezone(new DateTimeZone($user->timezone));
                    $message .= "\n- " . $name . ': ' . $this->formatDate($date);
                }
            } else {
                $message = 'Ты не создал ещё ни одной команды или не отправил мне ссылку на свой профиль';
            }
        }

        Message::send($chat['id'], $message, $user);
    }

    /**
     * Форматирует вывод даты
     */
    private function formatDate($date)
    {
        $result = $date->format('j') . ' ';

        $months = [
            1 => 'января',
            'февраля',
            'март',
            'апреля',
            'мая',
            'июня',
            'июля',
            'августа',
            'сентября',
            'октября',
            'ноября',
            'декабря',
        ];
        $result .= $months[$date->format('n')] . ' (';

        $days = [
            1 => 'пн',
            'вт',
            'ср',
            'чт',
            'пт',
            'сб',
            'вс',
        ];
        $result .= $days[$date->format('N')] . ') ';

        $result .= $date->format('H:i');

        return $result;
    }

    /**
     * Список команд пользователя
     */
    public function commandTeams($params, $chat)
    {
        $user = User::findOne(['chat_id' => $chat['id']]);
        if ($user === null) {
            $message = 'Кажется мы ещё не здоровались. Отправь мне /start';
        } else {
            if (count($user->teams) > 0) {
                $message = 'Твои команды:';
                foreach ($user->teams as $team) {
                    $message .= "\n- " . $team->name . ' (' . $team->tournament->name . ')';
                }
            } else {
                $message = 'Ты не создал ещё ни одной команды или не отправил мне ссылку на свой профиль';
            }
        }

        Message::send($chat['id'], $message, $user);
    }

    /**
     * Отписка от уведомлений
     */
    public function commandStop($params, $chat)
    {
        $user = User::findOne(['chat_id' => $chat['id']]);
        if ($user === null) {
            $message = 'Кажется мы ещё не здоровались. Отправь мне /start';
        } elseif ($user->notification) {
            $user->notification = false;
            $user->save();
            $message = 'Ты отписался от уведомлений. Если передумал, набери /start';
        } else {
            $message = 'Ты уже отписан от уведомлений. Если хочет снова подписаться, набери /start';
        }

        Message::send($chat['id'], $message, $user);
    }

    /**
     * Статус уведомлений
     */
    public function commandStatus($params, $chat)
    {
        $user = User::findOne(['chat_id' => $chat['id']]);
        if ($user === null) {
            $message = 'Кажется мы ещё не здоровались. Отправь мне /start';
        } elseif ($user->notification) {
            $message = 'Ты подписан на уведомления. Для отписки набери /stop';
        } else {
            $message = 'Ты отписан от уведомлений. Для подписки набери /start';
        }

        Message::send($chat['id'], $message, $user);
    }

    /**
     * Часовой пояс
     */
    public function commandTimezone($params, $chat)
    {
        $user = User::findOne(['chat_id' => $chat['id']]);
        if ($user === null) {
            $message = 'Кажется мы ещё не здоровались. Отправь мне /start';
        } elseif (count($params) == 1) {
            if (User::validateTimezone($params[0])) {
                $user->timezone = $params[0];
                $user->save();
                $message = 'Часовой пояс изменён. Для смены набери /timezone';
            } else {
                $message = "Некорректный часовой пояс\n\n";
                $message .= "Список доступных часовых поясов тут - https://telegra.ph/CHasovye-poyasa-02-03";
            }
        } else {
            $message = "Твой часовой пояс: {$user->timezone}\n";
            $date = new DateTime('now', new DateTimeZone($user->timezone));
            $message .= 'Текущее время: ' . $date->format('H:i') . "\n\n";
            $message .= "Чтоб сменить часовой пояс отправь команду: /timezone [часовой пояс]\n";
            $message .= "Например: /timezone Europe/London\n";
            $message .= "Список доступных часовых поясов тут - https://telegra.ph/CHasovye-poyasa-02-03";
        }

        Message::send($chat['id'], $message, $user);
    }

    /**
     * Время уведомлений
     */
    public function commandTime($params, $chat)
    {
        $user = User::findOne(['chat_id' => $chat['id']]);
        if ($user === null) {
            $message = 'Кажется мы ещё не здоровались. Отправь мне /start';
        } elseif (count($params) == 1) {
            $time = User::validateTime($params[0]);
            if ($time != "") {
                $date = new DateTime($time, new DateTimeZone($user->timezone));
                $date->setTimezone(new DateTimeZone(Yii::$app->timeZone));
                $user->notification_time = $date->format('H:i:s');
                $user->save();
                $message = 'Время уведомлений изменено. Для смены набери /time';
            } else {
                $message = "Некорректное время\n\n";
                $message .= "Отправь в формате 11:00, время должны быть кратно 30 минутам";
            }
        } else {
            $date = new DateTime($user->notification_time, new DateTimeZone(Yii::$app->timeZone));
            $date->setTimezone(new DateTimeZone($user->timezone));
            $message = 'Время уведомлений: ' . $date->format('H:i') . "\n";
            $message .= "Чтоб сменить отправь команду: /time [время]\n";
            $message .= "Например: /time 11:00";
        }

        Message::send($chat['id'], $message, $user);
    }

    /**
     * Настройки
     */
    public function commandSettings($params, $chat)
    {
        $user = User::findOne(['chat_id' => $chat['id']]);
        if ($user === null) {
            $message = 'Кажется мы ещё не здоровались. Отправь мне /start';
        } else {
            $message = 'Чтоб открыть настройки бота нажми на кнопку ниже';
            $keyboard = new InlineKeyboardMarkup([
                [
                    [
                        'text' => 'Настройки',
                        'web_app' => [
                            'url' => Url::to(['settings/app'], true),
                        ],
                    ],
                ],
            ]);
        }

        Message::send($chat['id'], $message, $user, null, $keyboard ?? null);
    }

    /**
     * Замены
     */
    public function commandTransfers($params, $chat)
    {
        $message = 'Чтоб сделать замены нажми на кнопку ниже';
        $keyboard = new InlineKeyboardMarkup([
            [
                [
                    'text' => 'Сделать замены',
                    'web_app' => [
                        'url' => 'https://www.sports.ru/fantasy/',
                    ],
                ],
            ],
        ]);

        Message::send($chat['id'], $message, null, null, $keyboard ?? null);
    }

    /**
     * Статистика бота
     */
    public function commandStat($params, $chat)
    {
        $user = User::findOne(['chat_id' => $chat['id']]);
        $stat = User::stat();
        $message = "Статистика\n\n";

        $message .= "Всего: {$stat['total']}\n";
        $message .= "С профилем: {$stat['profile']}\n";
        $message .= "Активные: {$stat['active']}\n";
        $message .= "Активные с профилем: {$stat['profile_active']}\n\n";

        Message::send($chat['id'], $message, $user);
    }

    /**
     * Помощь, список доступных команд
     */
    public function commandHelp($params, $chat)
    {
        $user = User::findOne(['chat_id' => $chat['id']]);
        $message = 'Вот команды, которые я понимаю:' . "\n";
        $message .= '/profile url - сообщить ссылку на свой профиль' . "\n";
        $message .= '/deadlines - дедлайны турниров' . "\n";
        $message .= '/teams - список твоих фентези-команд' . "\n";
        $message .= '/status - проверить статус подписки' . "\n";
        $message .= '/timezone zone - сменить часовой пояс' . "\n";
        $message .= '/time - сменить время уведомлений' . "\n";
        $message .= '/start - подписаться на уведомления' . "\n";
        $message .= '/stop - отписаться от уведомлений';

        Message::send($chat['id'], $message, $user);
    }

    /**
     * Неизвестная команда, ошибка
     */
    public function unknownCommand($chat)
    {
        $user = User::findOne(['chat_id' => $chat['id']]);
        $message = 'Я не понимаю тебя. Чтоб посмотреть список известных мне команд просто набери /help';
        Message::send($chat['id'], $message, $user);
    }
}

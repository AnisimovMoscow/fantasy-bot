<?php
namespace app\controllers;

use Yii;
use yii\web\Controller;
use TelegramBot\Api\BotApi;
use app\models\User;
use DateTime;
use DateTimeZone;

class SiteController extends Controller
{
    public function actionHook() {
        $update = Yii::$app->request->post();
		Yii::info(print_r($update, true));
		
		if ($update['message']['chat']['type'] == 'private' && isset($update['message']['text'])) {
			$params = explode(' ', $update['message']['text']);
			$command = array_shift($params);
			$method = 'command'.ucfirst(ltrim($command, '/'));
			if (method_exists($this, $method) && is_callable([$this, $method])) {
				$this->$method($params, $update['message']['chat']);
			} else {
				$this->unknownCommand($update['message']['chat']);
			}
		}
    }
	
	public function commandStart($params, $chat) {
		$user = User::findOne(['chat_id' => $chat['id']]);
		if ($user === null) {
			$user = new User([
				'chat_id' => $chat['id'],
				'first_name' => isset($chat['first_name'])? $chat['first_name'] : '',
				'last_name' => isset($chat['last_name'])? $chat['last_name'] : '',
				'username' => isset($chat['username'])? $chat['username'] : '',
				'profile_url' => '',
			]);
			$user->save();
			$message = 'Привет! Отправь мне ссылку на свой профиль и я буду напоминать тебе о важных событиях - /profile [url]';
		} elseif (empty($user->profile_url)) {
			$message = 'Ты ещё не отправил мне ссылку на свой профиль, набери /profile [url]';
		} else {
			$message = 'Привет! Если тебе нужна помощь, набери /help';
		}
		
		$bot = new BotApi(Yii::$app->params['token']);
		$bot->sendMessage($chat['id'], $message);
	}
	
	public function commandProfile($params, $chat) {
		if (count($params) == 1) {
			if (preg_match('/http:\/\/www\.sports\.ru\/profile\/\d+\//', $params[0])) {
				$user = User::findOne(['chat_id' => $chat['id']]);
				if ($user === null) {
					$message = 'Кажется мы ещё не здоровались. Отправь мне /start';
				} else {
					$user->profile_url = $params[0];
					$user->save();
					$user->updateTeams();
					$message = 'Всё отлично. Молодец! Я запомнил ссылку на твой профиль. Чтоб посмотреть список команд, отправь мне /teams';
				}
			} else {
				$message = 'Ты мне неправильно отправил ссылку. Просто зайди на сайт sports.ru и скопируй ссылку на свою страницу. Там должны быть ссылка вида http://www.sports.ru/profile/12345/';
			}
		} else {
			$message = 'Нужно просто написать /profile и через пробел ссылку на свой профиль';
		}
		
		$bot = new BotApi(Yii::$app->params['token']);
		$bot->sendMessage($chat['id'], $message);
	}
	
	public function commandDeadlines($params, $chat) {
		$user = User::findOne(['chat_id' => $chat['id']]);
		$formatter = Yii::$app->formatter;
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
					$date = new DateTime($deadline, new DateTimeZone(Yii::$app->timeZone));
					$message .= "\n- ".$name.': '.$formatter->asDatetime($date, 'd LLLL (E) HH:mm');
				}
			} else {
				$message = 'Ты не создал ещё ни одной команды или не отправил мне ссылку на свой профиль. Набери /profile [url]';
			}
		}
		
		$bot = new BotApi(Yii::$app->params['token']);
		$bot->sendMessage($chat['id'], $message);
	}
	
	public function commandTeams($params, $chat) {
		$user = User::findOne(['chat_id' => $chat['id']]);
		if ($user === null) {
			$message = 'Кажется мы ещё не здоровались. Отправь мне /start';
		} else {
			if (count($user->teams) > 0) {
				$message = 'Твои команды:';
				foreach ($user->teams as $team) {
					$message .= "\n- ".$team->name.' ('.$team->tournament->name.')';
				}
			} else {
				$message = 'Ты не создал ещё ни одной команды или не отправил мне ссылку на свой профиль. Набери /profile [url]';
			}
		}
		
		$bot = new BotApi(Yii::$app->params['token']);
		$bot->sendMessage($chat['id'], $message);
	}
	
	public function commandHelp($params, $chat) {
		$message = 'Вот команды, которые я понимаю:'."\n";
		$message .= '/profile [url] - сообщить ссылку на свой профиль'."\n";
		$message .= '/deadlines - дедлайны турниров'."\n";
		$message .= '/teams - список твоих фентези-команд';
		$bot = new BotApi(Yii::$app->params['token']);
		$bot->sendMessage($chat['id'], $message);
	}
	
	public function unknownCommand($chat) {
		$bot = new BotApi(Yii::$app->params['token']);
		$bot->sendMessage($chat['id'], 'Я не понимаю тебя. Чтоб посмотреть список известных мне команд просто набери /help');
	}
}
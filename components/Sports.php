<?php

namespace app\components;

use app\helpers\Request;

class Sports
{
    const GATEWAY = 'https://www.sports.ru/gql/graphql/';

    // Возвращает команды пользователя
    public static function getUserTeams($id)
    {
        $data = self::sendGql("{
            fantasyQueries {
                squads(input: {userID: {$id}}) {
                    id
                    name
                    season {
                        tournament {
                            name
                            webName
                        }
                    }
                }
            }
        }");
        if ($data === null || $data->data === null) {
            return [];
        }

        return $data->data->fantasyQueries->squads;
    }

    // Возвращает дедлайн команды
    public static function getTeamDeadline($id)
    {
        $data = self::sendGql("{
            fantasyQueries {
                squads(input: {squadID: {$id}}) {
                    currentTourInfo {
                        tour {
                            transfersFinishedAt
                        }
                    }
                }
            }
        }");
        if ($data->data === null) {
            return null;
        }

        return $data->data->fantasyQueries->squads[0]->currentTourInfo->tour->transfersFinishedAt;
    }

    // Отправляет GraphQL запрос
    private static function sendGql($query)
    {
        $json = Request::post(self::GATEWAY, [
            'operationName' => null,
            'query' => $query,
            'variables' => null,
        ], 'json');
        if ($json === null) {
            return null;
        }

        return json_decode($json);
    }
}

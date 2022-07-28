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
                    season {
                        currentTour {
                            transfersFinishedAt
                        }
                    }
                }
            }
        }");
        if ($data->data === null) {
            return null;
        }

        return $data->data->fantasyQueries->squads[0]->season->currentTour->transfersFinishedAt;
    }

    // Возвращает общее количество трансферов
    public static function getTeamTotalTransfers($id)
    {
        $data = self::sendGql("{
            fantasyQueries {
                squads(input: {squadID: {$id}}) {
                    season {
                        currentTour {
                            constraints {
                                totalTransfers
                            }
                        }
                    }
                }
            }
        }");

        return $data->data->fantasyQueries->squads[0]->season->currentTour->constraints->totalTransfers;
    }

    // Возвращает оставшиеся трансферы команды
    public static function getTeamTransfersLeft($id)
    {
        $data = self::sendGql("{
            fantasyQueries {
                squads(input: {squadID: {$id}}) {
                    currentTourInfo {
                        transfersLeft
                    }
                }
            }
        }");

        return $data->data->fantasyQueries->squads[0]->currentTourInfo->transfersLeft;
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

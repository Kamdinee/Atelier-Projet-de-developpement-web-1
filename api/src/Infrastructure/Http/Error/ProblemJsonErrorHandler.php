<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Http\Error;

use Nrv\Domain\Exception\DonneeInvalideException;
use Nrv\Domain\Exception\NrvException;
use Nrv\Domain\Exception\PlacesInsuffisantesException;
use Nrv\Domain\Exception\SoireeIntrouvableException;
use Nrv\Domain\Exception\SpectacleIntrouvableException;
use Nrv\Infrastructure\Http\Exception\RequeteInvalideException;
use Nrv\Infrastructure\Http\Json;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpException;
use Slim\Interfaces\ErrorHandlerInterface;
use Throwable;

// transforme toute exception en reponse json (format rfc 7807)
final class ProblemJsonErrorHandler implements ErrorHandlerInterface
{
    private const BASE_TYPE = 'https://nrv.net/erreurs/';

    public function __construct(private readonly ResponseFactoryInterface $responseFactory)
    {
    }

    public function __invoke(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails,
    ): ResponseInterface {
        [$statut, $type, $titre] = $this->classer($exception);

        $probleme = [
            'type' => self::BASE_TYPE . $type,
            'title' => $titre,
            'status' => $statut,
        ];

        // le detail des erreurs 500 n'est montre qu'en dev
        if ($statut < 500 || $displayErrorDetails) {
            $probleme['detail'] = $exception->getMessage();
        }

        if ($exception instanceof RequeteInvalideException) {
            $probleme['errors'] = $exception->erreurs;
        }
        if ($exception instanceof PlacesInsuffisantesException) {
            $probleme['errors'] = ['soirees' => [[
                'soiree_id' => $exception->soireeId,
                'demandees' => $exception->demandees,
                'restantes' => $exception->restantes,
            ]]];
        }

        if ($logErrors && $statut >= 500) {
            error_log((string) $exception);
        }

        $response = Json::reponse($this->responseFactory->createResponse(), $probleme, $statut, 'application/problem+json');

        // slim indique les methodes autorisees pour un 405
        if ($exception instanceof HttpException && $statut === 405 && method_exists($exception, 'getAllowedMethods')) {
            $response = $response->withHeader('Allow', implode(', ', $exception->getAllowedMethods()));
        }

        return $response;
    }

    /**
     * @return array{int, string, string} statut http, type, titre
     */
    private function classer(Throwable $e): array
    {
        return match (true) {
            $e instanceof RequeteInvalideException => [400, 'requete-invalide', 'Requête invalide'],
            $e instanceof SoireeIntrouvableException,
            $e instanceof SpectacleIntrouvableException => [404, 'introuvable', 'Ressource introuvable'],
            $e instanceof PlacesInsuffisantesException => [409, 'places-insuffisantes', 'Places insuffisantes'],
            $e instanceof DonneeInvalideException => [422, 'validation', 'Données invalides'],
            $e instanceof NrvException => [409, 'conflit', 'Conflit'],
            $e instanceof HttpException => match ($e->getCode()) {
                404 => [404, 'introuvable', 'Ressource introuvable'],
                405 => [405, 'methode-non-autorisee', 'Méthode non autorisée'],
                default => [$e->getCode(), 'erreur-http', $e->getTitle()],
            },
            default => [500, 'erreur-interne', 'Erreur interne'],
        };
    }
}

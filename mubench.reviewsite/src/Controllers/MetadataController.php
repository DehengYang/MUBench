<?php

namespace MuBench\ReviewSite\Controllers;

use MuBench\ReviewSite\Models\Metadata;
use MuBench\ReviewSite\Models\Pattern;
use MuBench\ReviewSite\Models\Type;
use Slim\Http\Request;
use Slim\Http\Response;

class MetadataController extends Controller
{

    public function putMetadata(Request $request, Response $response, array $args)
    {
        $this->logger->info("Put metadata.");
        $metadata = decodeJsonBody($request, true);
        if (!$metadata) {
            return error_response($response,400, 'empty: ' . print_r($request->getBody(), true));
        }
        foreach ($metadata as $misuseMetadata) {
            $projectId = $misuseMetadata['project'];
            $versionId = $misuseMetadata['version'];
            $misuseId = $misuseMetadata['misuse'];
            $description = $misuseMetadata['description'];
            $fix = $misuseMetadata['fix'];
            $location = $misuseMetadata['location'];
            $violationTypes = $misuseMetadata['violation_types'];
            $patterns = $misuseMetadata['patterns'];
            $targetSnippets = $misuseMetadata['target_snippets'];

            $this->updateMetadata($projectId, $versionId, $misuseId, $description, $fix, $location, $violationTypes, $patterns, $targetSnippets);
        }
        return $response->withStatus(200);
    }

    function updateMetadata($projectId, $versionId, $misuseId, $description, $fix, $location, $violationTypes, $patterns, $targetSnippets)
    {
        $this->logger->info("Update metadata for $projectId, $versionId, $misuseId");
        $metadata = $this->saveMetadata($projectId, $versionId, $misuseId, $description, $fix, $location);
        $this->saveViolationTypes($metadata, $violationTypes);
        $this->savePatterns($metadata->id, $patterns);
        $this->saveTargetSnippets($projectId, $versionId, $misuseId, $targetSnippets, $location['file']);
    }

    private function saveMetadata($projectId, $versionId, $misuseId, $description, $fix, $location)
    {
        $metadata = Metadata::firstOrNew(['project_muid' => $projectId, 'version_muid' => $versionId, 'misuse_muid' => $misuseId]);
        $metadata->description = $description;
        $metadata->fix_description = $fix['description'];
        $metadata->diff_url = $fix['diff-url'];
        $metadata->file = $location['file'];
        $metadata->method = $location['method'];
        $metadata->save();
        return $metadata;
    }

    private function saveViolationTypes($metadata, $violationTypeNames)
    {
        $this->logger->info("Save violation types.");
        $violationTypes = [];
        foreach ($violationTypeNames as $typeName) {
            $violationTypes[] = Type::firstOrCreate(['name' => $typeName])->id;
        }
        $metadata->violation_types()->sync($violationTypes);
    }

    private function savePatterns($metadataId, $patterns)
    {
        $this->logger->info("Save patterns.");
        if ($patterns) {
            foreach ($patterns as $pattern) {
                $p = Pattern::firstOrNew(['metadata_id' => $metadataId]);
                $p->code = $pattern['snippet']['code'];
                $p->line = $pattern['snippet']['first_line'];
                $p->save();
            }
        }
    }

    private function saveTargetSnippets($projectId, $versionId, $misuseId, $targetSnippets, $file)
    {
        $this->logger->info("Save target snippets.");
        if ($targetSnippets) {
            foreach ($targetSnippets as $snippet) {
                SnippetsController::createSnippetIfNotExists($projectId, $versionId, $misuseId, $file, $snippet['first_line_number'], $snippet['code']);
            }
        }
    }
}
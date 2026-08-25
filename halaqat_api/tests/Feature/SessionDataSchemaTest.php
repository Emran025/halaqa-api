<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SessionDataSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_annotation_tables_match_the_contract_shape(): void
    {
        $this->assertTrue(Schema::hasTable('mistakes'));
        $this->assertTrue(Schema::hasColumns('mistakes', ['tracking_detail_id', 'ayah_id', 'edition_id', 'word_index', 'mistake_type_id', 'source_role', 'deleted_at']));
        $this->assertTrue(Schema::hasTable('task_notes'));
        $this->assertTrue(Schema::hasColumns('task_notes', ['session_task_id', 'author_id', 'note', 'ayah_id', 'edition_id', 'deleted_at']));
        $this->assertTrue(Schema::hasTable('task_evaluations'));
        $this->assertTrue(Schema::hasColumns('task_evaluations', ['session_task_id', 'evaluator_id', 'evaluator_role', 'score', 'comment']));
    }
}

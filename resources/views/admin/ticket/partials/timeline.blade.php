@if($activityTimeline->isNotEmpty())
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0"><i class="bx bx-history me-1"></i> Activity Timeline</h5>
        </div>
        <div class="card-body">
            @foreach($activityTimeline as $activity)
                <div class="d-flex align-items-start mb-3">
                    <div class="flex-shrink-0 me-2">
                        @if($activity->causer)
                            <span class="badge bg-primary">{{ $activity->causer_name }}</span>
                        @else
                            <span class="badge bg-secondary">System</span>
                        @endif
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-medium">
                            {{ $activity->description }}
                        </div>
                        <div class="text-muted small">
                            {{ format_datetime($activity->created_at) }}
                        </div>
                        @if(!empty($activity->properties) && $activity->properties->count())
                            <div class="mt-2">
                                @if($activity->properties->has('old'))
                                    <strong>Changed:</strong>
                                    <ul class="list-unstyled mb-0">
                                        @foreach($activity->properties['old'] as $field => $oldValue)
                                            <li>{{ $field }}: "{{ $oldValue }}" → "{{ $activity->properties['attributes'][$field] ?? '' }}"</li>
                                        @endforeach
                                    </ul>
                                @endif
                                @if($activity->properties->has('attributes') && !$activity->properties->has('old'))
                                    <strong>Details:</strong>
                                    <ul class="list-unstyled mb-0">
                                        @foreach($activity->properties['attributes'] as $field => $value)
                                            <li>{{ $field }}: "{{ $value }}"</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
                <hr class="my-2" />
            @endforeach
            {{ $activityTimeline->links() }}
        </div>
    </div>
@else
    <p class="text-muted">No activity recorded.</p>
@endif

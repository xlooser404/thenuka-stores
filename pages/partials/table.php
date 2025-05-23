<?php
/**
 * Reusable Table Component for Soft UI Dashboard
 * 
 * @param string $title The title of the table
 * @param array $headers Array of header labels and classes
 * @param array $rows Array of row data
 * @param array $actions Array of action labels (e.g., ['view' => '<i class="fas fa-eye"></i>', ...])
 * @param string $add_button_label Label for the "Add" button (optional)
 * @param array $form_fields Fields for the "Add" modal form (optional)
 * @param string $form_action Action URL for the "Add" form (optional)
 * @param array $form_hidden_fields Hidden fields for the "Add" form (optional)
 */
function renderTable($title, $headers, $rows, $actions = [], $add_button_label = '', $form_fields = [], $form_action = '', $form_hidden_fields = []) {
?>
<div class="container-fluid">
  <?php if ($add_button_label && $form_fields && $form_action): ?>
    <div class="d-flex justify-content-end mb-3">
      <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
        <?php echo htmlspecialchars($add_button_label); ?>
      </button>
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="addModalLabel">Add <?php echo htmlspecialchars($title); ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form action="<?php echo htmlspecialchars($form_action); ?>" method="POST">
            <div class="modal-body">
              <?php foreach ($form_hidden_fields as $name => $value): ?>
                <input type="hidden" name="<?php echo htmlspecialchars($name); ?>" value="<?php echo htmlspecialchars($value); ?>">
              <?php endforeach; ?>
              <?php foreach ($form_fields as $name => $field): ?>
                <div class="mb-3">
                  <label for="<?php echo htmlspecialchars($name); ?>" class="form-label">
                    <?php echo htmlspecialchars($field['label']); ?>
                  </label>
                  <?php if ($field['type'] === 'text' || $field['type'] === 'email' || $field['type'] === 'password' || $field['type'] === 'number' || $field['type'] === 'date'): ?>
                    <input type="<?php echo htmlspecialchars($field['type']); ?>"
                      class="form-control"
                      id="<?php echo htmlspecialchars($name); ?>"
                      name="<?php echo htmlspecialchars($name); ?>"
                      <?php echo isset($field['step']) ? 'step="' . htmlspecialchars($field['step']) . '"' : ''; ?>
                      <?php echo isset($field['required']) && $field['required'] ? 'required' : ''; ?>
                      <?php echo ($field['type'] === 'number' || $field['type'] === 'date') ? 'min="' . ($field['type'] === 'number' ? '0' : '1900-01-01') . '"' : ''; ?>
                      <?php echo $field['type'] === 'date' ? 'max="' . date('Y-m-d') . '"' : ''; ?>>
                  <?php elseif ($field['type'] === 'textarea'): ?>
                    <textarea class="form-control"
                      id="<?php echo htmlspecialchars($name); ?>"
                      name="<?php echo htmlspecialchars($name); ?>"
                      rows="3"
                      <?php echo isset($field['required']) && $field['required'] ? 'required' : ''; ?>></textarea>
                  <?php elseif ($field['type'] === 'select'): ?>
                    <select class="form-select"
                      id="<?php echo htmlspecialchars($name); ?>"
                      name="<?php echo htmlspecialchars($name); ?>"
                      <?php echo isset($field['required']) && $field['required'] ? 'required' : ''; ?>>
                      <?php foreach ($field['options'] as $value => $label): ?>
                        <option value="<?php echo htmlspecialchars($value); ?>">
                          <?php echo htmlspecialchars($label); ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              <button type="submit" class="btn btn-primary">Add</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="card mb-4">
    <div class="card-header pb-0">
      <h6><?php echo htmlspecialchars($title); ?></h6>
    </div>
    <div class="card-body px-0 pt-0 pb-2">
      <div class="table-responsive p-0">
        <table class="table align-items-center mb-0">
          <thead>
            <tr>
              <?php foreach ($headers as $header): ?>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 <?php echo isset($header['class']) ? htmlspecialchars($header['class']) : ''; ?>">
                  <?php echo htmlspecialchars($header['label']); ?>
                </th>
              <?php endforeach; ?>
              <?php if (!empty($actions)): ?>
                <th class="text-secondary opacity-7"></th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $row): ?>
              <tr>
                <?php foreach ($row['data'] as $cell): ?>
                  <td <?php echo isset($cell['class']) ? 'class="' . htmlspecialchars($cell['class']) . '"' : ''; ?>>
                    <?php if (isset($cell['type']) && $cell['type'] === 'image'): ?>
                      <div class="d-flex px-2 py-1">
                        <div>
                          <img src="<?php echo htmlspecialchars($cell['value']); ?>" class="avatar avatar-sm me-3" alt="item">
                        </div>
                        <div class="d-flex flex-column justify-content-center">
                          <h6 class="mb-0 text-sm"><?php echo htmlspecialchars($cell['label']); ?></h6>
                          <p class="text-xs text-secondary mb-0"><?php echo htmlspecialchars($cell['subtext']); ?></p>
                        </div>
                      </div>
                    <?php elseif (isset($cell['type']) && $cell['type'] === 'badge'): ?>
                      <span class="badge badge-sm <?php echo htmlspecialchars($cell['badge_class']); ?>">
                        <?php echo htmlspecialchars($cell['value']); ?>
                      </span>
                    <?php elseif (isset($cell['type']) && $cell['type'] === 'progress'): ?>
                      <div class="d-flex align-items-center justify-content-center">
                        <span class="me-2 text-xs font-weight-bold"><?php echo htmlspecialchars($cell['value']); ?>%</span>
                        <div>
                          <div class="progress">
                            <div class="progress-bar <?php echo htmlspecialchars($cell['progress_class']); ?>" role="progressbar" aria-valuenow="<?php echo htmlspecialchars($cell['value']); ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo htmlspecialchars($cell['value']); ?>%;"></div>
                          </div>
                        </div>
                      </div>
                    <?php else: ?>
                      <span class="<?php echo isset($cell['text_class']) ? htmlspecialchars($cell['text_class']) : 'text-sm'; ?>">
                        <?php echo htmlspecialchars($cell['value']); ?>
                      </span>
                    <?php endif; ?>
                  </td>
                <?php endforeach; ?>
                <?php if (!empty($actions)): ?>
                  <td class="align-middle text-center">
                    <?php foreach ($actions as $action_key => $action_label): ?>
                      <a href="<?php echo isset($row['actions'][$action_key]) ? htmlspecialchars($row['actions'][$action_key]) : '#'; ?>"
                        class="action-button <?php echo $action_key === 'edit' ? 'action-edit text-warning' : ($action_key === 'delete' ? 'action-delete text-danger' : 'action-view text-info'); ?> mx-1"
                        data-bs-toggle="tooltip"
                        data-bs-title="<?php echo htmlspecialchars(ucfirst($action_key)); ?>"
                        data-action="<?php echo htmlspecialchars($action_key); ?>"
                        data-store='<?php echo htmlspecialchars(json_encode($row)); ?>'>
                        <?php echo $action_label; ?>
                      </a>
                    <?php endforeach; ?>
                  </td>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<style>
  .action-button {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
  }
  .action-button i {
    font-size: 1rem;
  }
  .action-view {
    color: #17a2b8;
  }
  .action-edit {
    color: #ffc107;
  }
  .action-delete {
    color: #dc3545;
  }
</style>
<?php
}
?>
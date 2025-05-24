<?php

/**
 * Reusable Table Component for Soft UI Dashboard
 */
function renderTable($title, $headers, $rows, $actions = []) {
?>
<div class="container-fluid">
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
                  <td class="align-middle">
                    <?php foreach ($actions as $action_key => $action_label): ?>
                      <a href="<?php echo isset($row['actions'][$action_key]) ? htmlspecialchars($row['actions'][$action_key]) : '#'; ?>" 
                         class="btn action-button <?php echo $action_key === 'edit' ? 'btn-warning action-edit' : ($action_key === 'delete' ? 'btn-danger action-delete' : 'btn-info'); ?> me-1"
                         data-bs-toggle="tooltip" 
                         data-bs-title="<?php echo htmlspecialchars(ucfirst($action_key)); ?>"
                         data-action="<?php echo htmlspecialchars($action_key); ?>"
                         data-store='<?php echo json_encode($row); ?>'>
                        <span class="d-flex align-items-center">
                          <?php echo $action_label; ?>
                          <span class="ms-1"><?php echo htmlspecialchars(ucfirst($action_key)); ?></span>
                        </span>
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
  }
  .action-button i {
    font-size: 1rem;
  }
</style>
<?php
}
?>
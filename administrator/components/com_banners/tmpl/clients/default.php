<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_banners
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');

HTMLHelper::_('behavior.multiselect');

$purchaseTypes = array(
		'1' => 'UNLIMITED',
		'2' => 'YEARLY',
		'3' => 'MONTHLY',
		'4' => 'WEEKLY',
		'5' => 'DAILY',
);

$user       = Factory::getUser();
$userId     = $user->get('id');
$listOrder  = $this->escape($this->state->get('list.ordering'));
$listDirn   = $this->escape($this->state->get('list.direction'));
$params     = $this->state->params ?? new JObject;
?>
<form action="<?php echo Route::_('index.php?option=com_banners&view=clients'); ?>" method="post" name="adminForm" id="adminForm">
	<div class="row">
		<div id="j-sidebar-container" class="col-md-2">
			<?php echo $this->sidebar; ?>
		</div>
		<div class="col-md-10">
			<div id="j-main-container" class="j-main-container">
				<?php
				// Search tools bar
				echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
				?>
				<?php if (empty($this->items)) : ?>
					<joomla-alert type="warning"><?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?></joomla-alert>
				<?php else : ?>
					<table class="table">
						<thead>
							<tr>
								<td style="width:1%" class="text-center">
									<?php echo HTMLHelper::_('grid.checkall'); ?>
								</td>
								<th scope="col" style="width:1%" class="nowrap text-center">
									<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
								</th>
								<th scope="col">
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_BANNERS_HEADING_CLIENT', 'a.name', $listDirn, $listOrder); ?>
								</th>
								<th scope="col" style="width:15%" class="d-none d-md-table-cell text-center">
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_BANNERS_HEADING_CONTACT', 'a.contact', $listDirn, $listOrder); ?>
								</th>
								<th scope="col" style="width:3%" class="nowrap text-center d-none d-md-table-cell">
                                    <span class="icon-publish hasTooltip" aria-hidden="true" title="<?php echo Text::_('COM_BANNERS_COUNT_PUBLISHED_ITEMS'); ?>">
                                        <span class="sr-only"><?php echo Text::_('COM_BANNERS_COUNT_PUBLISHED_ITEMS'); ?></span>
                                    </span>
								</th>
								<th scope="col" style="width:3%" class="nowrap text-center d-none d-md-table-cell">
                                    <span class="icon-unpublish hasTooltip" aria-hidden="true" title="<?php echo Text::_('COM_BANNERS_COUNT_UNPUBLISHED_ITEMS'); ?>">
                                        <span class="sr-only"><?php echo Text::_('COM_BANNERS_COUNT_UNPUBLISHED_ITEMS'); ?></span>
                                    </span>
								</th>
								<th scope="col" style="width:3%" class="nowrap text-center d-none d-md-table-cell">
                                    <span class="icon-archive hasTooltip" aria-hidden="true" title="<?php echo Text::_('COM_BANNERS_COUNT_ARCHIVED_ITEMS'); ?>">
                                        <span class="sr-only"><?php echo Text::_('COM_BANNERS_COUNT_ARCHIVED_ITEMS'); ?></span>
                                    </span>
								</th>
								<th scope="col" style="width:3%" class="nowrap text-center d-none d-md-table-cell">
                                    <span class="icon-trash hasTooltip" aria-hidden="true" title="<?php echo Text::_('COM_BANNERS_COUNT_TRASHED_ITEMS'); ?>">
                                        <span class="sr-only"><?php echo Text::_('COM_BANNERS_COUNT_TRASHED_ITEMS'); ?></span>
                                    </span>
								</th>
								<th scope="col" style="width:10%" class="nowrap d-none d-md-table-cell text-center">
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_BANNERS_HEADING_PURCHASETYPE', 'a.purchase_type', $listDirn, $listOrder); ?>
								</th>
								<th scope="col" style="width:3%" class="nowrap d-none d-md-table-cell text-center">
									<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
								</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($this->items as $i => $item) :
								$canCreate  = $user->authorise('core.create',     'com_banners');
								$canEdit    = $user->authorise('core.edit',       'com_banners');
								$canCheckin = $user->authorise('core.manage',     'com_checkin') || $item->checked_out == $user->get('id') || $item->checked_out == 0;
								$canChange  = $user->authorise('core.edit.state', 'com_banners') && $canCheckin;
								?>
								<tr class="row<?php echo $i % 2; ?>">
									<td class="text-center">
										<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
									</td>
									<td class="text-center">
										<div class="btn-group">
											<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'clients.', $canChange); ?>
										</div>
									</td>
									<th scope="row" class="nowrap has-context">
										<div>
											<?php if ($item->checked_out) : ?>
												<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'clients.', $canCheckin); ?>
											<?php endif; ?>
											<?php if ($canEdit) : ?>
												<?php $editIcon = $item->checked_out ? '' : '<span class="fa fa-pencil-square mr-2" aria-hidden="true"></span>'; ?>
												<a class="hasTooltip" href="<?php echo Route::_('index.php?option=com_banners&task=client.edit&id=' . (int) $item->id); ?>" title="<?php echo Text::_('JACTION_EDIT'); ?> <?php echo $this->escape(addslashes($item->name)); ?>">
													<?php echo $editIcon; ?><?php echo $this->escape($item->name); ?></a>
											<?php else : ?>
												<?php echo $this->escape($item->name); ?>
											<?php endif; ?>
										</div>
									</th>
									<td class="small d-none d-md-table-cell text-center">
										<?php echo $item->contact; ?>
									</td>
									<td class="center btns d-none d-lg-table-cell">
										<a class="badge <?php if ($item->count_published > 0) echo 'badge-success'; ?>" href="<?php echo Route::_('index.php?option=com_banners&view=banners&filter[client_id]=' . (int) $item->id . '&filter[published]=1'); ?>">
											<?php echo $item->count_published; ?></a>
									</td>
									<td class="center btns d-none d-lg-table-cell">
										<a class="badge <?php if ($item->count_unpublished > 0) echo 'badge-danger'; ?>" href="<?php echo Route::_('index.php?option=com_banners&view=banners&filter[client_id]=' . (int) $item->id . '&filter[published]=0'); ?>">
											<?php echo $item->count_unpublished; ?></a>
									</td>
									<td class="center btns d-none d-lg-table-cell">
										<a class="badge <?php if ($item->count_archived > 0) echo 'badge-info'; ?>" href="<?php echo Route::_('index.php?option=com_banners&view=banners&filter[client_id]=' . (int) $item->id . '&filter[published]=2'); ?>">
											<?php echo $item->count_archived; ?></a>
									</td>
									<td class="center btns d-none d-lg-table-cell">
										<a class="badge <?php if ($item->count_trashed > 0) echo 'badge-inverse'; ?>" href="<?php echo Route::_('index.php?option=com_banners&view=banners&filter[client_id]=' . (int) $item->id . '&filter[published]=-2'); ?>">
											<?php echo $item->count_trashed; ?></a>
									</td>
									<td class="small d-none d-md-table-cell text-center">
										<?php if ($item->purchase_type < 0) : ?>
											<?php echo Text::sprintf('COM_BANNERS_DEFAULT', Text::_('COM_BANNERS_FIELD_VALUE_' . $purchaseTypes[$params->get('purchase_type')])); ?>
										<?php else : ?>
											<?php echo Text::_('COM_BANNERS_FIELD_VALUE_' . $purchaseTypes[$item->purchase_type]); ?>
										<?php endif; ?>
									</td>
									<td class="d-none d-md-table-cell text-center">
										<?php echo $item->id; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

					<?php // load the pagination. ?>
					<?php echo $this->pagination->getListFooter(); ?>

				<?php endif; ?>

				<input type="hidden" name="task" value="">
				<input type="hidden" name="boxchecked" value="0">
				<?php echo HTMLHelper::_('form.token'); ?>
			</div>
		</div>
	</div>
</form>

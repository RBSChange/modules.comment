<?php
class PHPTAL_Php_Attribute_CHANGE_Starrating extends ChangeTalAttribute
{
	/**
	 * @var integer
	 */
	private static $idCounter = 0;
	
	/**
	 * @return array
	 */
	protected function getDefaultValues()
	{
		return array('name' => 'changeStarrating' . self::$idCounter, 'value' => 0, 'inline' => true, 'displayOnly' => false, 
			'small' => false);
	}
	
	/**
	 * @param array $params
	 */
	public static function renderStarrating($params)
	{
		$controller = website_BlockController::getInstance();
		$context = $controller->getContext();
		if (Framework::getConfigurationValue('modules/comment/add-starrating-styles') == 'true')
		{
			$context->addStyle('modules.comment.starrating-default');
		}
		
		$currentAction = $controller->getProcessedAction();
		if ($currentAction !== null)
		{
			$currentValue = $controller->getRequest()->getParameter($params['name'], 0);
			$moduleName = $currentAction->getModuleName();
		}
		else
		{
			$moduleName = change_Controller::getInstance()->getRequest()->getParameter('module');
			$moduleValues = change_Controller::getInstance()->getRequest()->getModuleParameters($moduleName);
			$currentValue = $moduleValues[$params['name']];
		}
		
		$ls = LocaleService::getInstance();
		if ($params['displayOnly'] === false)
		{
			echo '<ol class="star-rating-accessible">';
			echo '<label for="rating-accessible-input-' . self::$idCounter . '-0" class="option-label">0</label><input id="rating-accessible-input-' . self::$idCounter . '-0" value="0" ' . ($currentValue == 0 ? 'checked="checked"' : '') . ' name="' . $moduleName . 'Param[' . $params['name'] . ']"  class="option-label" type="radio">';
			echo '<label for="rating-accessible-input-' . self::$idCounter . '-1" class="option-label">1</label><input id="rating-accessible-input-' . self::$idCounter . '-1" value="1" ' . ($currentValue == 1 ? 'checked="checked"' : '') . ' name="' . $moduleName . 'Param[' . $params['name'] . ']"  class="option-label" type="radio">';
			echo '<label for="rating-accessible-input-' . self::$idCounter . '-2" class="option-label">2</label><input id="rating-accessible-input-' . self::$idCounter . '-2" value="2" ' . ($currentValue == 2 ? 'checked="checked"' : '') . ' name="' . $moduleName . 'Param[' . $params['name'] . ']"  class="option-label" type="radio">';
			echo '<label for="rating-accessible-input-' . self::$idCounter . '-3" class="option-label">3</label><input id="rating-accessible-input-' . self::$idCounter . '-3" value="3" ' . ($currentValue == 3 ? 'checked="checked"' : '') . ' name="' . $moduleName . 'Param[' . $params['name'] . ']"  class="option-label" type="radio">';
			echo '<label for="rating-accessible-input-' . self::$idCounter . '-4" class="option-label">4</label><input id="rating-accessible-input-' . self::$idCounter . '-4" value="4" ' . ($currentValue == 4 ? 'checked="checked"' : '') . ' name="' . $moduleName . 'Param[' . $params['name'] . ']"  class="option-label" type="radio">';
			echo '<label for="rating-accessible-input-' . self::$idCounter . '-5" class="option-label">5</label><input id="rating-accessible-input-' . self::$idCounter . '-5" value="5" ' . ($currentValue == 5 ? 'checked="checked"' : '') . ' name="' . $moduleName . 'Param[' . $params['name'] . ']"  class="option-label" type="radio">';
			echo '</ol>';
		}
		
		echo '<ul class="star-rating';
		echo ($params['displayOnly'] === false) ? ' accessible-hidden' : '';
		echo ($params['small'] === true) ? ' small-star' : '';
		echo ($params['inline'] == true) ? ' inline-rating' : '';
		echo '" id="change-starrating-' . self::$idCounter . '">';
		$currentRating = intval(round(floatval($params['value']) * 20));
		$currentRating = $currentRating - $currentRating % 10;
		
		echo '<li class="current-rating rating-' . $currentRating . ' star">' . $ls->trans('m.comment.frontoffice.current-star-rating', array(
			'html'), array("rating" => round(floatval($params['value'])))) . '</li>';
		if ($params['displayOnly'] === false)
		{
			$format = array('attr');
			echo '<li class="star"><a href="#' . self::$idCounter . '" title="' . $ls->trans('m.comment.frontoffice.star-rating-1', $format) . '" class="one-star' . ($currentValue == 1 ? ' clicked' : '') . '">1</a></li>';
			echo '<li class="star"><a href="#' . self::$idCounter . '" title="' . $ls->trans('m.comment.frontoffice.star-rating-2', $format) . '" class="two-stars' . ($currentValue == 2 ? ' clicked' : '') . '">2</a></li>';
			echo '<li class="star"><a href="#' . self::$idCounter . '" title="' . $ls->trans('m.comment.frontoffice.star-rating-3', $format) . '" class="three-stars' . ($currentValue == 3 ? ' clicked' : '') . '">3</a></li>';
			echo '<li class="star"><a href="#' . self::$idCounter . '" title="' . $ls->trans('m.comment.frontoffice.star-rating-4', $format) . '" class="four-stars' . ($currentValue == 4 ? ' clicked' : '') . '">4</a></li>';
			echo '<li class="star"><a href="#' . self::$idCounter . '" title="' . $ls->trans('m.comment.frontoffice.star-rating-5', $format) . '" class="five-stars' . ($currentValue == 5 ? ' clicked' : '') . '">5</a></li>';
		}
		echo '</ul>';
		self::$idCounter++;
	}
	
	/**
	 * @return boolean
	 */
	protected function evaluateAll()
	{
		return true;
	}
}
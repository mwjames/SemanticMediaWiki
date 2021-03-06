<?php

namespace SMW\MediaWiki\Hooks;

use SMW\MediaWiki\HtmlFormBuilder;
use SMW\ApplicationFactory;

use SMW\DIProperty;
use EditPage;

/**
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/EditPage::showEditForm:initial
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class EditPageForm {

	/**
	 * @var EditPage
	 */
	private $editPage = null;

	/**
	 * @var HtmlFormBuilder
	 */
	private $htmlFormBuilder = null;

	/**
	 * @var ApplicationFactory
	 */
	private $applicationFactory = null;

	/**
	 * @since  2.1
	 *
	 * @param EditPage $editPage
	 * @param HtmlFormBuilder $htmlFormBuilder
	 */
	public function __construct( EditPage $editPage, HtmlFormBuilder $htmlFormBuilder ) {
		$this->editPage = $editPage;
		$this->htmlFormBuilder = $htmlFormBuilder;
		$this->applicationFactory = ApplicationFactory::getInstance();
	}

	/**
	 * @since 2.1
	 *
	 * @return boolean
	 */
	public function process() {
		return $this->canAddHelpForm() ? $this->addHelpForm() : true;
	}

	private function addHelpForm() {

		$message = $this->htmlFormBuilder
			->getMessageBuilder()
			->getMessage( $this->findMessageKeyFor( $this->editPage->getTitle()->getNamespace() ) )
			->parse();

		$html = $this->htmlFormBuilder
			->setName( 'editpage-help' )
			->addParagraph( $message , array( 'class' => 'smw-editpage-help' ) )
			->getForm();

		$this->editPage->editFormPageTop .= $html;

		return true;
	}

	private function findMessageKeyFor( $namespace ) {

		if ( $this->isPropertyPage( $namespace ) ) {
			if ( DIProperty::newFromUserLabel( $this->editPage->getTitle()->getText() )->isUserDefined() ) {
				return 'smw-editpage-property-annotation-enabled';
			} else {
				return 'smw-editpage-property-annotation-disabled';
			}
		} elseif ( $this->isConceptPage( $namespace ) ) {
			return 'smw-editpage-concept-annotation-enabled';
		} elseif ( $this->isSemanticEnabledPage( $namespace ) ) {
			return 'smw-editpage-annotation-enabled';
		}

		return 'smw-editpage-annotation-disabled';
	}

	private function isPropertyPage( $namespace ) {
		return $namespace === SMW_NS_PROPERTY;
	}

	private function isConceptPage( $namespace ) {
		return $namespace === SMW_NS_CONCEPT;
	}

	private function isSemanticEnabledPage( $namespace ) {
		return $this->applicationFactory->getNamespaceExaminer()->isSemanticEnabled( $namespace );
	}

	private function canAddHelpForm() {
		return $this->applicationFactory->getSettings()->get( 'smwgEnabledEditPageHelp' );
	}

}

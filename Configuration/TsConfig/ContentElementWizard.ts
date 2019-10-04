mod.wizards.newContentElement {
    wizardItems {
        special {
            elements {
                h5p_view {
                    iconIdentifier = h5p-logo
                    title = LLL:EXT:h5p/Resources/Private/Language/Tca.xlf:h5p.wizard.title
                    description = LLL:EXT:h5p/Resources/Private/Language/Tca.xlf:h5p.wizard.description
                    tt_content_defValues {
                        CType = h5p_view
                    }
                }
                h5p_statistics {
                    iconIdentifier = h5p-logo
                    title = LLL:EXT:h5p/Resources/Private/Language/Tca.xlf:h5p.statistics
                    description = LLL:EXT:h5p/Resources/Private/Language/Tca.xlf:h5p.statistics.description
                    tt_content_defValues {
                        CType = h5p_statistics
                    }
                }
            }

            show := addToList(h5p_view)
        }
    }
}

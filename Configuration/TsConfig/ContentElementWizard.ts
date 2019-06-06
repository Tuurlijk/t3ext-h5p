mod.wizards.newContentElement {
    wizardItems {
        special {
            elements {
                h5p_view {
                    icon = EXT:h5p/Resources/Public/Icon/h5p.gif
                    title = LLL:EXT:h5p/Resources/Private/Language/Tca.xlf:h5p.wizard.title
                    description = LLL:EXT:h5p/Resources/Private/Language/Tca.xlf:h5p.wizard.description
                    tt_content_defValues {
                        CType = h5p_view
                    }
                }
            }
            show := addToList(h5p_view)
        }
    }
}


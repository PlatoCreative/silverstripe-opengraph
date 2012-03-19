<?php

/**
 * Adds open graph functionality to a page or data object
 *
 * @author Damian Mooyman
 */
class OpenGraphPageExtension extends DataObjectDecorator implements IOGObjectExplicit
{
    public static $default_image = '/opengraph/images/logo.gif';

    /**
     * Property for retrieving the opengraph namespace html tag(s).
     * This should be inserted into your Page.SS template as: "<html $OGNS>"
     * @return string The HTML tag to use for the opengraph namespace(s)
     */
    public function getOGNS()
    {
        // todo : Should custom namespace be injected here, or left up to user code?
        
        $ns = ' xmlns:og="http://ogp.me/ns#" xmlns:fb="http://www.facebook.com/2008/fbml"';
        if ($this->owner instanceof IOGMusic)
            $ns .= ' xmlns:music="http://ogp.me/ns/music#"';
        if ($this->owner instanceof IOGVideo)
            $ns .= ' xmlns:video="http://ogp.me/ns/video#"';
        if ($this->owner instanceof IOGArticle)
            $ns .= ' xmlns:article="http://ogp.me/ns/article#"';
        if ($this->owner instanceof IOGBook)
            $ns .= ' xmlns:book="http://ogp.me/ns/book#"';
        if ($this->owner instanceof IOGProfile)
            $ns .= ' xmlns:profile="http://ogp.me/ns/profile#"';

        // Since the default type is website we should make sure that the correct namespace is applied in the default case
        if ($this->owner instanceof IOGWebsite || $this->owner->getOGType() == OGTypes::DefaultType)
            $ns .= ' xmlns:website="http://ogp.me/ns/website#"';

        return $ns;
    }


    /**
     * Determines the tag builder to use for this object
     * @return IOpenGraphObjectBuilder
     */
    protected function getTagBuilder()
    {
        // Determine type
        $type = $this->owner->getOGType();
        if(empty($type))
            return null;
        
        // Determine prototype specification for this object type
        $types = OpenGraph::$object_types;
        if(!isset($types[$type]))
            return null;
        $prototype = OpenGraph::$object_types[$type];
        
        // Build tag builder for this prototype
        $builderClass = $prototype[1];
        return new $builderClass();
    }

    public function MetaTags(&$tags)
    {
        // Generate tag builder
        $builder = $this->getTagBuilder();
        if(!$builder)
            return;
        
        $config = SiteConfig::current_site_config();
        // Default tags
        $builder->BuildTags($tags, $this->owner, $config);
    }

    /**
     * Determines the opengraph type identifier for this object
     * @return string
     */
    public function getOGType()
    {
        foreach(OpenGraph::$object_types as $type => $details)
        {
            $interface = $details[0];
            if ($this->owner instanceof $interface)
                return $type;
        }

        return OGTypes::DefaultType;
    }

    public function getOGTitle()
    {
        /**
         * @see DataObject::getTitle()
         */
        return $this->owner->Title;
    }

    public function getOGSiteName()
    {
        $config = SiteConfig::current_site_config();
        return $config->Title;
    }

    public function OGImage()
    {
        // Since og:image is a required property, provide a reasonable default
        if (self::$default_image)
            return Director::absoluteURL(self::$default_image);
    }

    public function AbsoluteLink()
    {
        // Left blank by default. Implement this in the decorated class to determine correct value
    }

    public function OGAudio()
    {
        // No audio by default
    }

    public function OGVideo()
    {
        // No video by default
    }

    public function getOGDescription()
    {
        // Intelligent fallback for SiteTree instances
        $contentField = $this->owner->dbObject('Content');
        if ($contentField instanceof Text)
            return $contentField->FirstParagraph();
    }

    public function getOGDeterminer()
    {
        return OGDeterminers::DefaultValue;
    }

    public function getOGLocales()
    {
        return i18n::get_locale();
    }

}
<?php

namespace App\Helpers;

class HeroSliderHelper
{
    public static function getTransitionEffects()
    {
        return [
            'fade' => 'Fade',
            'slideLeft' => 'Slide Left',
            'slideRight' => 'Slide Right',
            'slideUp' => 'Slide Up',
            'slideDown' => 'Slide Down',
            'zoom' => 'Zoom',
            'cube' => 'Cube',
            'flip' => 'Flip',
            'coverflow' => 'Coverflow'
        ];
    }
    
    public static function getTextAnimations()
    {
        return [
            'fadeInUp' => 'Fade In Up',
            'fadeInDown' => 'Fade In Down',
            'fadeInLeft' => 'Fade In Left',
            'fadeInRight' => 'Fade In Right',
            'slideInUp' => 'Slide In Up',
            'slideInDown' => 'Slide In Down',
            'zoomIn' => 'Zoom In',
            'bounceIn' => 'Bounce In',
            'typewriter' => 'Typewriter',
            'none' => 'None'
        ];
    }
    
    public static function getContentLayouts()
    {
        return [
            'center' => 'Center',
            'left' => 'Left Aligned',
            'right' => 'Right Aligned',
            'split' => 'Split Screen',
            'fullwidth' => 'Full Width'
        ];
    }
    
    public static function getTemplates()
    {
        return [
            'default' => 'Default',
            'minimal' => 'Minimal',
            'bold' => 'Bold',
            'elegant' => 'Elegant',
            'modern' => 'Modern',
            'corporate' => 'Corporate',
            'creative' => 'Creative'
        ];
    }
    
    public static function getButtonStyles()
    {
        return [
            'primary' => 'Primary (Solid)',
            'secondary' => 'Secondary (Outline)',
            'gradient' => 'Gradient',
            'ghost' => 'Ghost',
            'icon-only' => 'Icon Only'
        ];
    }
    
    public static function getOverlayPatterns()
    {
        return [
            '' => 'None',
            'dots' => 'Dots',
            'lines' => 'Lines',
            'grid' => 'Grid',
            'diagonal' => 'Diagonal',
            'circular' => 'Circular',
            'noise' => 'Noise'
        ];
    }
    
    public static function getBadgeColors()
    {
        return [
            'blue' => 'Blue',
            'red' => 'Red',
            'green' => 'Green',
            'yellow' => 'Yellow',
            'purple' => 'Purple',
            'orange' => 'Orange',
            'pink' => 'Pink'
        ];
    }
}


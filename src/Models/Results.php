<?php

namespace Swis\GotLaravel\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Tekstblok
 */
class Results extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = array(
      'remote',
      'filename',
      'line',
      'author',
      'author_normalized',
      'author_slug',
      'email',
      'date',
      'commitHash',
      'parser',
      'created_at',
      'updated_at',
      'file',
      'parser',
    );

    /**
     * Create or update a record matching the attributes, and fill it with values.
     *
     * @param  array $attributes
     * @param  array $values
     * @return static
     */
    public static function updateOrCreate(
      array $attributes,
      array $values = array()
    ) {
        $instance = static::firstOrNew($attributes);

        $instance->fill($values)->save();

        return $instance;
    }

    public function getUrl()
    {
        if (strpos($this->remote, 'bitbucket.org') > 0) {
            return str_replace(
              '.git',
              '',
              str_replace(
                'git@',
                'https://',
                str_replace(':', '/', $this->remote)
              )
            ).'/commits/'.$this->commitHash.'#L'.$this->filename.'T'.$this->line;
        } elseif (strpos($this->remote, 'github.com') > 0) {
            return str_replace(
              '.git',
              '',
              str_replace('git@', 'https://', $this->remote)
            ).'/blame/master/'.$this->filename.'#L'.$this->line;
        } else {
            return '#';
        }
    }
}

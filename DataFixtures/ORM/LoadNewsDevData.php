<?php

namespace Awaresoft\Sonata\NewsBundle\DataFixtures\ORM;

use Awaresoft\Doctrine\Common\DataFixtures\AbstractFixture as AwaresoftAbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Sonata\NewsBundle\Model\CommentInterface;

/**
 * Class LoadPageData
 *
 * @author Bartosz Malec <b.malec@awaresoft.pl>
 *
 */
class LoadNewsDevData extends AwaresoftAbstractFixture
{
    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 10;
    }

    /**
     * {@inheritDoc}
     */
    public function getEnvironments()
    {
        return array('dev');
    }

    public function doLoad(ObjectManager $manager)
    {
        $postManager = $this->getPostManager();
        $faker = $this->getFaker();
        $tags = array(
            'symfony' => null,
            'form' => null,
            'general' => null,
            'web2' => null,
        );

        foreach ($tags as $tagName => $null) {
            $tag = $this->getTagManager()->create();
            $tag->setEnabled(true);
            $tag->setName($tagName);

            $tags[$tagName] = $tag;
            $this->getTagManager()->save($tag);
        }

        $collection = $this->getCollectionManager()->create();
        $collection->setEnabled(true);
        $collection->setName('General');
        $this->getCollectionManager()->save($collection);

        foreach (range(1, 20) as $id) {
            $post = $postManager->create();

            $post->setCollection($collection);
            $post->setAbstract($faker->sentence(30));
            $post->setEnabled(true);
            $post->setTitle($faker->sentence(6));
            $post->setPublicationDateStart($faker->dateTimeBetween('-30 days', '-1 days'));
            $post->setMetaTitle('test');
            $post->setMetaDescription('test');
            $post->setSite($this->getReference('page-site'));

            $id = $this->getReference('sonata-media-0')->getId();

            $raw = <<<RAW
### Gist Formatter

Now a specific gist from github

<% gist '1552362', 'gistfile1.txt' %>

### Media Formatter

Load a media from a <code>SonataMediaBundle</code> with a specific format

<% media $id, 'big' %>

RAW;

            $raw .= sprintf("### %s\n\n%s\n\n### %s\n\n%s",
                $faker->sentence(rand(3, 6)),
                $faker->text(1000),
                $faker->sentence(rand(3, 6)),
                $faker->text(1000)
            );

            $post->setRawContent($raw);
            $post->setContentFormatter('text');

            $post->setContent($this->getPoolFormatter()->transform($post->getContentFormatter(), $post->getRawContent()));
            $post->setCommentsDefaultStatus(CommentInterface::STATUS_VALID);

            foreach ($tags as $tag) {
                $post->addTags($tag);
            }

            foreach (range(1, $faker->randomDigit + 2) as $commentId) {
                $comment = $this->getCommentManager()->create();
                $comment->setEmail($faker->email);
                $comment->setName($faker->name);
                $comment->setStatus(CommentInterface::STATUS_VALID);
                $comment->setMessage($faker->sentence(25));
                $comment->setUrl($faker->url);

                $post->addComments($comment);
            }

            $postManager->save($post);
        }
    }

    public function getPoolFormatter()
    {
        return $this->container->get('sonata.formatter.pool');
    }

    /**
     * @return \Sonata\CoreBundle\Model\ManagerInterface
     */
    public function getTagManager()
    {
        return $this->container->get('sonata.classification.manager.tag');
    }

    /**
     * @return \Sonata\CoreBundle\Model\ManagerInterface
     */
    public function getCollectionManager()
    {
        return $this->container->get('sonata.classification.manager.collection');
    }

    /**
     * @return \Sonata\NewsBundle\Model\PostManagerInterface
     */
    public function getPostManager()
    {
        return $this->container->get('sonata.news.manager.post');
    }

    /**
     * @return \Sonata\NewsBundle\Model\CommentManagerInterface
     */
    public function getCommentManager()
    {
        return $this->container->get('sonata.news.manager.comment');
    }

}

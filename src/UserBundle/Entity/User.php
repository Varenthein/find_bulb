<?php
namespace UserBundle\Entity;


use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
/**
 * @ORM\Entity(repositoryClass="UserBundle\Repository\UserRepository")
 * @ORM\Table(name="users")
 */
class User extends BaseUser
{
    public function __construct()
    {
        parent::__construct();
        // your own logic
        $this->passwordExpireAt = new \DateTime('next month');
        $this->roles = new ArrayCollection();
    }
    
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    

   
    /**
     * @var ArrayCollection
     * @ORM\ManyToMany(targetEntity="Role", inversedBy="users", fetch="EAGER", cascade={"persist"})
     * @ORM\JoinTable(name="user_to_role")
     */
    protected $roles;
 
    
    public function getFullUsername(){
        return $this->firstname.' '.$this->lastname;
    }
    //Jak user nie ma zadnej roli to zwraca rolę uzytkownika
    public function getRolesWithDisplayName(){
        if (! $this->roles->count()) {
            return array(
                array(
                    'name' => parent::ROLE_DEFAULT,
                    'displayName' => 'User'
                )
            );
        }
        $roles = $this->roles->toArray();
        
        return $roles;
    }
    //Jak user nie ma zadnej roli to zwraca null
    public function getRolesWithDisplayNameNoDefault(){
        if (! $this->roles->count()) {
            return null;
        }
        $roles = $this->roles->toArray();
        
        return $roles;
    }
    
    
    public function getRoles(){
        if (! $this->roles->count()) {
            return array(parent::ROLE_DEFAULT);
        }
        $roles = $this->roles->toArray();
        foreach ($this->getGroups() as $group) {
            $roles = array_merge($roles, $group->getRoles());
        }
        foreach ($roles as $k => $role) {
            /* 
             * Ensure String[] to prevent bad unserialized UsernamePasswordToken with for instance 
             * UPT#roles:{Role('ROLE_USER'), 'ROLE_USER'} which ends in Error: Call to a member 
             * function getRole() on a non-object
             */
            $roles[$k] = $role instanceof RoleInterface ? $role->getRole() : (string) $role; 
        }
        
        return array_flip(array_flip($roles));
    }
    public function getRoleById($roleId){
        $roles = $this->roles->toArray();
        foreach ($this->getGroups() as $group) {
            $roles = array_merge($roles, $group->getRoles());
        }
        foreach ($roles as $r){
            if($r->getId() == $roleId){
                return true;
            }
        }
        return false;
    }
    
    
    
    public function addRole($role){
        !($role instanceof Role) && $role = new Role($role);
        $role->addUser($this, false);
        $this->roles->add($role);
        return $this;
    }
        
    public function removeRole($role){
        $role = $this->roles->filter(
                    function(Role $r) use ($role) {
                        if ($role instanceof Role) {
                            return $r->getRole() === $role->getRole();
                        } else {
                            return $r->getRole() === strtoupper($role);
                        }
                    }
                )->first();
        if ($role) {
            $this->roles->removeElement($role);
        }    
        return $this;
    }
    
    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }
}

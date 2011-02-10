<?php

//namespace util;

/**
 * This class represents an access control mechanism.
 * A resource is an object to which access is controlled.
 * A role is an object that may request access to a Resource.
 * Through the specification and use of an ACL (access control list), an application may control how roles are granted access to resources. 
 *
 * @package util
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class Acl
{
	/**
	 * Registered roles.
	 * @var mixed[]
	 */
	protected $roles = array();
	/**
	 * Registered resources.
	 * @var mixed[]
	 */
	protected $resources = array();
	/**
	 * Registered privileges.
	 * @var mixed[]
	 */
	protected $privileges = array();
	/**
	 * Indicates if ACL is restrictive (deny all) or permissive (allow all)
	 * @var boolean
	 */
	protected $restrictive;
	
	/**
	 * Constructor.
	 *
	 * @param boolean $restrictive
	 */
	public function __construct($restrictive = true)
	{
		$this->restrictive = $restrictive;
	}
	
	/**
	 * Add/remove rules.
	 *
	 * @param boolean $add Add or remove privilege
	 * @param boolean $allow Allow or deny privilege
	 * @param mixed|mixed[] $roles NULL indicates all the registered roles.
	 * @param mixed|mixed[] $resources NULL indicates all the registered resources.
	 * @param mixed|mixed[] $privileges
	 * @throws InvalidArgumentException if $privileges is null.
	 */
	protected function _setRule($add, $allow, $roles, $resources, $privileges)
	{
		if ($privileges === null) {
			throw new InvalidArgumentException("NULL is not a valid privilege.");
		}
		
		if ($roles != null) {
			$roles = (array)$roles;
			foreach ($roles as $r) {
				if (!$this->hasRole($r)) {
					throw new InvalidArgumentException("'$r' is not a valid role.");
				}
			}
		}
		else {
			$roles = $this->roles;
		}
		if ($resources != null) {
			$resources = (array)$resources;
			foreach ($resources as $r) {
				if (!$this->hasResource($r)) {
					throw new InvalidArgumentException("'$r' is not a valid resource.");
				}
			}
		}
		else {
			$resources = array_keys($this->resources);
		}

		$privileges = (array)$privileges;
		
		if ($add) {
			// add privileges
			foreach ($roles as $role) {
				foreach ($resources as $r) {
					if (!isset($this->privileges[$role][$r])) {
						$this->privileges[$role][$r] = array();
					}
					foreach ($privileges as $p) {
						$this->privileges[$role][$r][$p] = $allow;
					}
				}
			}
		}
		else {
			// remove privileges
			foreach ($roles as $role) {
				foreach ($resources as $r) {
					if (!isset($this->privileges[$role][$r])) {
						continue;
					}
					foreach ($privileges as $p) {
						unset($this->privileges[$role][$r][$p]);
					}
				}
			}
		}
	}
	
	/**
	 * Find a previlege recursively, if not found the default privilege is returned.
	 *
	 * @param mixed $role
	 * @param mixed $resource
	 * @param mixed $privilege
	 * @return boolean
	 */
	protected function _getPrivilege($role, $resource, $privilege)
	{
		$priv = isset($this->privileges[$role][$resource][$privilege]) ? 
						$this->privileges[$role][$resource][$privilege] : null;
		
		if ($priv == null && is_array($this->resources[$resource]['parent'])) {
			$resources = $this->resources[$resource]['parent'];
			do {
				$resourcep = array_pop($resources);
				if ($resourcep === null) break;
				$priv = $this->_getPrivilege($role, $resourcep, $privilege);
			} while ($priv == null);
		}

		if ($priv == null && is_array($this->roles[$role]['parent'])) {
			$roles = $this->roles[$role]['parent'];
			do {
				$rolep = array_pop($roles);
				if ($rolep === null) break;
				$priv = $this->_getPrivilege($rolep, $resource, $privilege);
			} while ($priv == null);
		}
		
		return $priv == null ? !$this->restrictive : $priv;
	}
	
	/**
	 * Check if a role exists
	 *
	 * @param mixed $role
	 * @return boolean
	 */
	public function hasRole($role)
	{
		return isset($this->roles[$role]);
	}
	
	/**
	 * Check if a resource exists
	 *
	 * @param mixed $resource
	 * @return boolean
	 */
	public function hasResource($resource)
	{
		return isset($this->resources[$resource]);
	}
	
	/**
	 * Get registered roles
	 *
	 * @return mixed[]
	 */
	public function getRoles()
	{
		return $this->roles;
	}
	
	/**
	 * Get registered resources
	 *
	 * @return mixed[]
	 */
	public function getResources()
	{
		return $this->resources;
	}
	
	/**
	 * Add a new role that has an unique identifier.
	 * Optionally, you can provide a parent role or an array of roles to indicate the role(s) from which the newly added role will directly inherit.
	 * If an array is supplied, the last one will have the highest priority.
	 *
	 * @param mixed $role
	 * @param mixed|mixed[] $parents A single value or an array of values
	 * @throws InvalidArgumentException if $role already exists.
	 */
	public function addRole($role, $parents = null)
	{
		if ($this->hasRole($role)) {
			throw new InvalidArgumentException("Cannot add duplicate role '$role'.");
		}
		$this->privileges[$role] = array();
		$this->roles[$role] = array(
			'parent' => (array)$parents
		);
	}
	
	/**
	 * Add a new resource that has an unique identifier.
	 * Optionally, you can provide a parent resource or an array of resources to indicate the resource(s) from which the newly added resource will directly inherit.
	 * If an array is supplied, the last one will have the highest priority.
	 *
	 * @param mixed $resource
	 * @param mixed|mixed[] $parents A single value or an array of values
	 * @throws InvalidArgumentException if $resource already exists.
	 */
	public function addResource($resource, $parents = null)
	{
		if ($this->hasResource($resource)) {
			throw new InvalidArgumentException("Cannot add duplicate resource '$resource'.");
		}
		$this->resources[$resource] = array(
			'parent' => $parents
		);
	}
	
	/**
	 * Add privileges to a role for the specified resource. You can provide a single value or an array of values.
	 * Make sure you call this method after registering all the roles and resources.
	 *
	 * @param mixed|mixed[] $role A single role or an array of roles. NULL indicates all the registered roles.
	 * @param mixed|mixed[] $resource A single resource or an array of resources. NULL indicates all the registered resources.
	 * @param mixed|mixed[] $privileges A single privilege or an array of privileges.
	 * @throws InvalidArgumentException if $privileges is null.
	 */
	public function allow($role, $resource, $privileges)
	{
		$this->_setRule(true, true, $role, $resource, $privileges);
	}
	
	/**
	 * Remove privileges from a role for the specified resource. You can provide a single value or an array of values.
	 * Make sure you call this method after registering all the roles and resources.
	 *
	 * @param mixed|mixed[] $role A single role or an array of roles. NULL indicates all the registered roles.
	 * @param mixed|mixed[] $resource A single resource or an array of resources. NULL indicates all the registered resources.
	 * @param mixed|mixed[] $privileges A single privilege or an array of privileges.
	 * @throws InvalidArgumentException if $privileges is null.
	 */
	public function removeAllow($role, $resource, $privileges)
	{
		$this->_setRule(false, true, $role, $resource, $privileges);
	}
	
	/**
	 * Deny privileges to a role for the specified resource. You can provide a single value or an array of values.
	 * Make sure you call this method after registering all the roles and resources.
	 *
	 * @param mixed|mixed[] $role A single role or an array of roles. NULL indicates all the registered roles.
	 * @param mixed|mixed[] $resource A single resource or an array of resources. NULL indicates all the registered resources.
	 * @param mixed|mixed[] $privileges A single privilege or an array of privileges.
	 * @throws InvalidArgumentException if $privileges is null.
	 */
	public function deny($role, $resource, $privileges)
	{
		$this->_setRule(true, false, $role, $resource, $privileges);
	}
	
	/**
	 * Remove denial of privileges from a role for the specified resource. You can provide a single value or an array of values.
	 * Make sure you call this method after registering all the roles and resources.
	 *
	 * @param mixed|mixed[] $role A single role or an array of roles. NULL indicates all the registered roles.
	 * @param mixed|mixed[] $resource A single resource or an array of resources. NULL indicates all the registered resources.
	 * @param mixed|mixed[] $privileges A single privilege or an array of privileges.
	 * @throws InvalidArgumentException if $privileges is null.
	 */
	public function removeDeny($role, $resource, $privileges)
	{
		$this->_setRule(false, false, $role, $resource, $privileges);
	}
	
	/**
	 * Checks if a role has privileges to access a resource.
	 *
	 * @param mixed $role A valid registered role.
	 * @param mixed|mixed[] $resources A single value or an array of values. NULL indicates all the registered resources.
	 * @param mixed|mixed[] $privileges A single value or an array of values.
	 * @return boolean
	 * @throws InvalidArgumentException if $privileges is null, or any role or resource is invalid.
	 */
	public function isAllowed($role, $resources, $privileges)
	{
		if ($privileges == null) {
			throw new InvalidArgumentException("Invalid privileges supplied.");
		}
		
		if (!$this->hasRole($role)) {
			throw new InvalidArgumentException("'$role' is not a valid registered role.");
		}
		
		if ($resources != null) {
			$resources = (array)$resources;
			foreach ($resources as $r) {
				if (!$this->hasResource($r)) {
					throw new InvalidArgumentException("'$r' is not a valid registered resource.");
				}
			}
		}
		else {
			$resources = $this->resources;
		}
		
		$privileges = (array)$privileges;
		$privilegeCount = 0;
		foreach ($resources as $r) {
			foreach ($privileges as $p) {
				// TODO check if privilege exists...
				$privilege = $this->_getPrivilege($role, $r, $p);
				if (!$privilege) {
					return false;
				}
				$privilegeCount++;
			}
		}
		
		return $privilegeCount == (count($resources) * count($privileges));
	}
}

?>

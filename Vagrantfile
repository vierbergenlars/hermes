Vagrant.configure(2) do |config|
    config.vm.box = "debian/jessie64"
    config.vm.synced_folder ".", "/vagrant", type: "virtualbox", mount_options: ["fmode=0666", "dmode=0777"]

    config.vm.provision :shell, path: "provisioning/install-ansible.sh"
    config.vm.provision :shell, inline: "PYTHONUNBUFFERED=1 sudo ansible-playbook /vagrant/provisioning/all.yml --connection=local"

    config.vm.define "hermes", primary: true do |hermes|
        hermes.vm.network "private_network", ip: "192.168.80.9"
        hermes.vm.synced_folder "src", "/var/www/src", create: true, type: "virtualbox", mount_options: ["fmode=0666", "dmode=0777"]
        hermes.vm.provision :shell, inline: "PYTHONUNBUFFERED=1 sudo ansible-playbook /vagrant/provisioning/hermes.yml --connection=local"
    end

    config.vm.define "authserver", autostart: false do |authserver|
        authserver.vm.network "private_network", ip: "192.168.80.2"
        authserver.vm.provision :shell, inline: "PYTHONUNBUFFERED=1 sudo ansible-playbook /vagrant/provisioning/authserver.yml --connection=local"
    end

    config.vm.provider "virtualbox" do |vb|
        vb.gui = false
        vb.cpus = 1
        vb.memory = 512
    end

end

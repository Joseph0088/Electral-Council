#!/bin/bash
echo "updating system..."
sudo apt-get update -y
echo "cleaning up ..."
sudo apt-get clean all
echo "All done !"

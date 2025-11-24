<?php
namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use App\Repository\OfferRepository;
use App\Entity\Offer;
class OfferService
{
    private $session;
    private $offerRepository;

    public function __construct(RequestStack $requestStack, OfferRepository $offerRepository)
        {
            $this->session = $requestStack->getSession();
            $this->offerRepository = $offerRepository;
        }
  
    public function datesOk(Offer $offer): bool
        {
            if(($offer->getStartDate() < $offer->getEndDate()) ){ 
               return true;
            }
            else {
                return false;
            }
            
        }
    public function priceOK(Offer $offer): bool
        {
            if($offer->getOfferPrice() > 0 && $offer->getOfferPrice() < $offer->getProduct()->getPrice()){ 
               return true;
            }
            else {
                return false;
            }
            
        }
    
    public function offerActive(Offer $offer): bool
    {

        if ($this->offerRepository->findActiveForProduct($offer->getProduct(), new \DateTimeImmutable())){
            return true;
        }
        return false;
    }
}

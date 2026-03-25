# Sequence Diagrams - School Inventory Management System

This document contains actor-focused sequence diagrams showing the flow of actions between users in the School Inventory Management System.

---

## 1. Request Creation and Fulfillment

```mermaid
sequenceDiagram
    autonumber
    actor D as Director
    actor HR as HR Manager
    actor SM as Stock Manager
    
    D->>HR: Submits request with items
    HR->>HR: Reviews request details
    
    alt Request Approved
        HR->>SM: Approves request for fulfillment
        SM->>SM: Checks stock availability
        
        alt Sufficient Stock
            SM->>SM: Picks items from warehouse
            SM->>D: Delivers/fulfills request
            Note over SM: System auto-generates<br/>Bon de Sortie
            Note over SM: System decreases<br/>item stock
        else Insufficient Stock
            SM->>HR: Creates Purchase Order for missing items
            HR->>HR: Reviews and approves PO
            SM->>SM: Orders from supplier
            SM->>SM: Receives stock
            SM->>D: Delivers/fulfills request
        end
        
    else Request Rejected
        HR->>D: Rejects request with reason
    end
```

---

## 2. Purchase Order Lifecycle

```mermaid
sequenceDiagram
    autonumber
    actor SM as Stock Manager
    actor HR as HR Manager
    
    SM->>HR: Submits Purchase Order for initial approval
    HR->>HR: Reviews PO details and items
    
    alt PO Meets Criteria
        HR->>SM: Provides initial approval
        
        Note over SM: System sets status to<br/>"Initial Approved"
        
        SM->>SM: Collects supplier proposals
        SM->>HR: Submits supplier proposals for review
        
        HR->>HR: Compares supplier quotes and quality ratings
        
        alt Best Supplier Selected
            HR->>SM: Provides final approval with selected supplier
            
            Note over SM: System sets status to<br/>"Final Approved"
            
            SM->>SM: Marks PO as ordered
            
            Note over SM: System sets status to<br/>"Ordered"
            
        else No Suitable Supplier
            HR->>SM: Rejects PO
        end
        
    else PO Does Not Meet Criteria
        HR->>SM: Rejects PO with feedback
    end
```

---

## 3. Invoice Management

```mermaid
sequenceDiagram
    autonumber
    actor FM as Finance Manager
    actor SM as Stock Manager
    
    FM->>SM: Creates invoice (linked to approved PO or manual entry)
    FM->>SM: Adds invoice line items
    FM->>SM: Uploads invoice document
    FM->>SM: Submits invoice for processing
    
    alt Invoice Type: Incoming (linked to PO)
        Note over FM: System increases item stock<br/>and marks PO as invoiced
        
        SM->>SM: Verifies received inventory matches invoice
        SM->>FM: Confirms inventory verification
        
    else Invoice Type: Return
        Note over FM: System records return transaction
        SM->>FM: Acknowledges return
        
    else Invoice Type: Standalone
        SM->>FM: Reviews invoice for accuracy
        SM->>FM: Provides feedback or confirmation
    end
    
    FM->>SM: Marks invoice as processed/completed
```

---

## 4. Bon de Sortie Generation (Triggered by Request Fulfillment)

```mermaid
sequenceDiagram
    autonumber
    actor SM as Stock Manager
    actor HR as HR Manager
    actor D as Director
    
    HR->>SM: Approves director's request
    SM->>SM: Reviews request for fulfillment
    
    alt Full Fulfillment
        SM->>D: Issues items to director
        Note over SM: System generates<br/>Bon de Sortie
        Note over SM: System records<br/>stock decrease
        
    else Partial Fulfillment
        SM->>D: Issues available items
        SM->>HR: Notifies of remaining items needed
        HR->>SM: Approves Purchase Order for missing items
        SM->>D: Issues remaining items when received
    end
    
    D->>SM: Signs/confirms receipt of items
```

---

## Appendix: Actor Responsibilities Summary

| Actor | Role in Workflows |
|-------|------------------|
| **Director** | Initiates requests, receives fulfilled items |
| **HR Manager** | Reviews and approves requests, approves purchase orders |
| **Stock Manager** | Manages inventory, fulfills requests, creates POs, verifies received stock |
| **Finance Manager** | Creates and manages invoices, links to POs |

---

## Status Flow Reference

### Request Status
```
Pending → HR Approved → Fulfilled
    ↓
  Rejected
```

### Purchase Order Status
```
Pending Initial Approval → Initial Approved → Pending Final Approval → Final Approved → Ordered
         ↓                                                    ↓
       Rejected                                          Rejected
```

---

*Generated for Complexe Scolaire AL AMINE - School Inventory Management System*

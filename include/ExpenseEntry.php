<?php

class ExpenseEntry extends Teleskope
{
    use TopicAttachmentTrait;
    use TopicCustomFieldsTrait;

    public static function GetExpenseEntry(int $id): ?ExpenseEntry
    {
        $expense_entry = Budget2::GetBudgetUse($id);
        return self::Hydrate($id, $expense_entry);
    }

    public static function GetTopicType(): string
    {
        return self::TOPIC_TYPES['EXPENSE_ENTRY'];
    }
}

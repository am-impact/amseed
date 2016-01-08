# amseed

_Seed an Element Type with dummy data in Craft_

## Generate dummies

The plugin will search for all available Element Types, and gives you the option to create dummies for them. Keep in mind that, when you have **your own Element Type**, you need to specify which **service** and **save method** the Element Type uses. Otherwise it doesn't know how to create a dummy for it. Because of this, when you generate a dummy generator, it'll **only show** Element Types that also have their service and save method specified in the settings. By default, it'll show **Categories, Entries and Users**.

While creating a dummy generator, it'll display several options. It will search for available **sources** and **attributes**. For each attribute, you can set a specific value or you could set a **random element, email address** or **dummy text**.

There's one exception for the **User Element Type**. When you want to generate dummy users, it'll use the [Random User API](https://randomuser.me/) for generating a good looking first and lastname, email address and username. So you don't have to specify these in the attributes, because they will simply be overridden.

When you've created a new dummy generator, it'll **start creating** the dummies when you've saved it. It will start up a **task**, and create the dummies **per set**, specified in the **plugin's settings**. When the task is finished, you can simply **restart** the dummy generator again for more dummies. You will also receive a **simple log** of the dummies that were **successfully generated**, and how many **failed** with the **errors** that occurred.

![DummyGenerators](https://raw.githubusercontent.com/am-impact/am-impact.github.io/master/img/readme/amseed/generators.png "DummyGenerators")

![NewGenerator](https://raw.githubusercontent.com/am-impact/am-impact.github.io/master/img/readme/amseed/dummy-generator.png "NewGenerator")

![GeneralSettings](https://raw.githubusercontent.com/am-impact/am-impact.github.io/master/img/readme/amseed/general.png "GeneralSettings")

![ElementTypeSettings](https://raw.githubusercontent.com/am-impact/am-impact.github.io/master/img/readme/amseed/element-types.png "ElementTypeSettings")

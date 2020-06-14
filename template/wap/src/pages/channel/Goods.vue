<template>
  <div class="channel-goods bg-f8">
    <HeadSearch :disabled="false" showLeft show-action placeholder="商品名称" @rightAction="onSearch" />
    <CategoryView top="46" :bottom="viewBottom">
      <div slot="nav" class="nav">
        <div
          class="item"
          v-for="(item,index) in category_list"
          :key="index"
          :class="activeKey == index ? 'active' : ''"
          @click="onChangeNav(index)"
        >
          <span class="nav-item-text">{{item.short_name || item.category_name}}</span>
        </div>
      </div>
      <List
        slot="content"
        v-model="loading"
        :finished="finished"
        :error.sync="error"
        :is-empty="isListEmpty"
        :empty="{message: '没有相关商品'}"
        @load="loadList"
      >
        <GoodsPanelGroup v-for="(item,index) in list" :key="index" :detail="item" />
      </List>
    </CategoryView>
    <GoodsSubmitBar ref="goodsCart" />
  </div>
</template>

<script>
import sfc from "@/utils/create";
import HeadSearch from "@/components/HeadSearch";
import CategoryView from "@/components/CategoryView";
import GoodsPanelGroup from "./component/GoodsPanelGroup";
import GoodsSubmitBar from "./component/GoodsSubmitBar";
import { GET_GOODSCATEGORY, GET_GOODSLIST } from "@/api/channel";
import { isEmpty } from "@/utils/util";
import { list } from "@/mixins";
export default sfc({
  name: "channel-goods",
  data() {
    const buy_type = this.$route.params.type;
    return {
      activeKey: 0,
      buy_type,
      category_list: [],
      params: {
        category_id: "",
        buy_type,
        search_text: ""
      }
    };
  },
  mixins: [list],
  watch: {
    "$route.params.type": function(type, o) {
      if (
        this.$route.name == "channel-goods" &&
        type &&
        this.buy_type !== type
      ) {
        this.activeKey = 0;
        this.buy_type = type;
        this.params.buy_type = type;
        this.params.search_text = "";
        this.category_list = [];
        this.loadData("init");
        this.$refs.goodsCart.getCartList();
      }
    }
  },
  computed: {
    viewBottom() {
      // 采购情况需要显示最小采购金额
      if (this.$route.params.type == "purchase") {
        return this.$store.state.channel.isAchieveCondie ? 50 : 90;
      } else {
        return 50;
      }
    }
  },
  mounted() {
    this.loadData();
  },
  methods: {
    onChangeNav(index) {
      this.activeKey = index;
      this.params.category_id = this.category_list[index].category_id;
      this.loadList("init");
    },
    onSearch(value) {
      this.params.search_text = value;
      this.loadList("init");
    },
    getCategory() {
      const $this = this;
      return new Promise((resolve, reject) => {
        GET_GOODSCATEGORY($this.buy_type)
          .then(({ data }) => {
            $this.category_list = data.category_list;
            resolve();
          })
          .catch(() => {});
      });
    },
    loadList(init) {
      const $this = this;
      if (init && init === "init") {
        $this.initList();
      }
      GET_GOODSLIST($this.params)
        .then(({ data }) => {
          let list = data.goods_list ? data.goods_list : [];
          $this.pushToList(list, data.page_count, init);
        })
        .catch(() => {
          $this.loadError();
        });
    },
    loadData(init) {
      const $this = this;
      if (isEmpty($this.category_list)) {
        $this.getCategory().then(() => {
          $this.params.category_id = $this.category_list[$this.activeKey]
            ? $this.category_list[$this.activeKey].category_id
            : "";
          $this.loadList(init);
        });
      } else {
        $this.params.category_id = $this.category_list[$this.activeKey]
          ? $this.category_list[$this.activeKey].category_id
          : "";
        $this.loadList(init);
      }
    }
  },
  components: {
    HeadSearch,
    CategoryView,
    GoodsPanelGroup,
    GoodsSubmitBar
  }
});
</script>
<style scoped>
.nav .item {
  color: #323232;
  display: flex;
  align-items: center;
  height: 46px;
  justify-content: center;
  position: relative;
}

.nav .item.active .nav-item-text::before {
  content: "";
  position: absolute;
  display: block;
  width: 2px;
  height: 16px;
  background: #ff454e;
  left: 0;
  top: 50%;
  margin-top: -8px;
}

.nav .item.active {
  color: #ff454e;
  background: #fff;
}

.content {
  background-color: #fff;
  flex: 1;
  position: relative;
}
</style>

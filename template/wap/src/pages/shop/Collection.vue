<template>
  <div class="shop-collection bg-f8">
    <template v-if="$store.state.config.addons.shop">
      <Navbar />
      <List
        v-model="loading"
        :finished="finished"
        :error.sync="error"
        :is-empty="isListEmpty"
        @load="loadList"
      >
        <van-cell
          v-for="(item,index) in list"
          :key="index"
          clickable
          :to="{name:'shop-home',params:{shopid:item.shop_id}}"
        >
          <div class="item">
            <div class="img">
              <img v-lazy="item.shop_logo" :key="item.shop_logo" pic-type="shop" />
            </div>
            <div class="text">
              <div class="name">{{item.shop_name}}</div>
              <van-row type="flex" justify="space-between">
                <van-col class="text-maintone"></van-col>
                <van-col class="van-col-icon">
                  <van-icon name="like" />
                </van-col>
              </van-row>
            </div>
          </div>
        </van-cell>
      </List>
    </template>
    <Empty v-else page-type="fail" message="未开启店铺应用" :show-foot="false" />
  </div>
</template>

<script>
import sfc from "@/utils/create";
import { GET_SHOPCOLLECTLIST } from "@/api/shop";
import { list } from "@/mixins";
import Empty from "@/components/Empty";
export default sfc({
  name: "shop-collection",
  data() {
    return {};
  },
  mixins: [list],
  activated() {
    if (this.$store.state.config.addons.shop == 1) this.loadList("init");
  },
  methods: {
    loadList(init) {
      const $this = this;
      if (init && init === "init") {
        $this.initList();
      }
      GET_SHOPCOLLECTLIST($this.params)
        .then(({ data }) => {
          let list = data.shop_list;
          $this.pushToList(list, data.page_count, init);
        })
        .catch(() => {
          $this.loadError();
        });
    }
  },
  components: {
    Empty
  }
});
</script>

<style scoped>
.item {
  display: flex;
}

.item .img {
  width: 110px;
  height: 64px;
  margin-right: 10px;
}

.item .img img {
  display: block;
  width: 100%;
  height: 100%;
}

.item .text {
  flex: 1;
}

.item .text .name {
  height: 40px;
  line-height: 20px;
  word-break: break-all;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.van-col-icon {
  display: flex;
  align-items: center;
}
.van-icon {
  font-size: 16px;
}
</style>

<template>
  <div>
    <van-cell title="物流公司" :value="name" is-link @click="show=true" />
    <div>
      <van-popup v-model="show" position="bottom" class="popup">
        <div>
          <HeadSearch
            :disabled="false"
            showLeft
            show-action
            :leftClick="onLeftClick"
            @rightAction="search"
          />
          <List
            v-model="loading"
            :finished="finished"
            :error.sync="error"
            @load="loadList"
            class="list"
          >
            <van-cell
              clickable
              v-for="(item,index) in list"
              :key="index"
              :title="item.company_name"
              @click="select(item)"
            />
          </List>
        </div>
      </van-popup>
    </div>
  </div>
</template>

<script>
import HeadSearch from "@/components/HeadSearch";
import { GET_EXPRESSCOMPANY } from "@/api/order";
import { list } from "@/mixins";
export default {
  data() {
    return {
      show: false,
      params: {
        search_text: ""
      }
    };
  },
  props: {
    name: String
  },
  mixins: [list],
  mounted() {
    this.loadList();
  },
  methods: {
    onLeftClick() {
      this.show = false;
    },
    search(e) {
      this.params.search_text = e;
      this.loadList("init");
    },
    select(item) {
      this.show = false;
      this.$emit("select", item);
    },
    loadList(init) {
      const $this = this;
      if (init && init === "init") {
        $this.initList();
      }
      GET_EXPRESSCOMPANY($this.params)
        .then(({ data }) => {
          let list = data.expressList || [];
          $this.pushToList(list, data.page_count, init);
        })
        .catch(() => {
          $this.loadError();
        });
    }
  },
  components: {
    HeadSearch
  }
};
</script>

<style scoped>
.popup {
  width: 100%;
  height: 100%;
  border-radius: 0;
}

.list {
  height: calc(100vh - 46px);
  overflow-y: auto;
}
</style>